import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const localConfigPath = path.join(root, 'collector.local.json');
const config = fs.existsSync(localConfigPath)
  ? JSON.parse(fs.readFileSync(localConfigPath, 'utf8'))
  : {};
const resolveFromRoot = (value, fallback) => path.resolve(root, value || fallback);
const envPath = resolveFromRoot(config.mixpostEnvPath, '../../../pc-mixpost/.env');
const env = Object.fromEntries(fs.readFileSync(envPath, 'utf8').split(/\r?\n/).filter(line => line && !line.startsWith('#')).map(line => {
  const at = line.indexOf('='); return [line.slice(0, at), line.slice(at + 1)];
}));
const token = env.MIXPOST_THREADS_COLLECTOR_TOKEN || env.APP_KEY;
const baseUrl = env.APP_URL || 'http://localhost:9000';
const edge = config.edgePath || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const profile = resolveFromRoot(config.profilePath, 'profile');
const loginOnly = process.argv.includes('--login');
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const compact = value => {
  if (!value) return null;
  const normalized = value.replace(/表示|回|,/g, '').trim();
  const match = normalized.match(/^([\d.]+)(万|億)?$/);
  if (!match) return null;
  return Math.round(Number(match[1]) * (match[2] === '億' ? 100000000 : match[2] === '万' ? 10000 : 1));
};
const actionNumber = (labels, name) => {
  const label = labels.find(value => value.includes(name));
  return compact(label?.replace(/^.*?(\d)/, '$1'));
};
const api = async (url, body = {}) => {
  const response = await fetch(`${baseUrl}${url}`, {method:'POST', headers:{Authorization:`Bearer ${token}`,'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(body)});
  if (response.status === 204) return null;
  if (!response.ok) throw new Error(`Mixpost API ${response.status}: ${await response.text()}`);
  return response.json();
};

const context = await chromium.launchPersistentContext(profile, {executablePath: edge, headless: !loginOnly, viewport:{width:1280,height:900}});
const page = context.pages()[0] || await context.newPage();
page.setDefaultTimeout(5000);
page.setDefaultNavigationTimeout(20000);
if (loginOnly) {
  await page.goto('https://www.threads.com/');
  console.log('Threadsへログイン後、このブラウザを閉じてください。');
  await context.waitForEvent('close').catch(() => {});
  process.exit(0);
}

let job;
try {
  job = await api('/mixpost/collector/threads/claim');
  if (!job) { console.log('処理待ちの調査はありません。'); await context.close(); process.exit(0); }

  const candidateCounts = new Map();
  for (const keyword of job.keywords) {
    console.log(`検索: ${keyword}`);
    await page.goto(`https://www.threads.com/search?q=${encodeURIComponent(keyword)}&serp_type=default`);
    await wait(3500);
    const hrefs = await page.locator('a[href*="/post/"]').evaluateAll(elements => elements.map(element => element.getAttribute('href')).filter(Boolean));
    for (const href of new Set(hrefs)) {
      const match = href.match(/^\/@([^/]+)\/post\//);
      if (match) candidateCounts.set(match[1].toLowerCase(), (candidateCounts.get(match[1].toLowerCase()) || 0) + 1);
    }
    await wait(1500);
  }

  const handles = [...candidateCounts.entries()].sort((a,b) => b[1]-a[1]).slice(0, job.candidate_limit).map(([handle]) => handle);
  const accounts = [];
  for (const handle of handles) {
    console.log(`候補: @${handle}`);
    await page.goto(`https://www.threads.com/@${handle}`); await wait(3000);
    let links = [];
    for (let scroll = 0; scroll < 6; scroll++) {
      links = await page.locator(`a[href^="/@${handle}/post/"]`).evaluateAll(elements => [...new Set(elements.map(element => element.href))].filter(href => /\/post\/[^/]+$/.test(href)));
      if (links.length >= job.posts_per_account) break;
      await page.evaluate(() => window.scrollBy(0, Math.max(window.innerHeight * 2, 1400)));
      await wait(1800);
    }
    const posts = [];
    for (const postUrl of links.slice(0, job.posts_per_account)) {
      console.log(`  取得: ${postUrl}`);
      await page.goto(postUrl); await wait(2200);
      const viewTexts = await page.getByText(/表示.*回/).allTextContents().catch(() => []);
      const labels = await page.getByRole('button').allTextContents();
      const contentCandidates = await page.locator('div[dir="auto"], span[dir="auto"]').allTextContents().catch(() => []);
      const content = contentCandidates.map(value => value.trim())
        .filter(value => value.length >= 12 && !/^(Threads|フォロー|返信|いいね|再投稿|シェア)$/u.test(value))
        .sort((a, b) => b.length - a.length)[0] || null;
      posts.push({post_url:postUrl, content, views:compact(viewTexts[0]), reactions:actionNumber(labels,'いいね'), replies:actionNumber(labels,'返信'), reposts:actionNumber(labels,'再投稿')});
      await wait(1800);
    }
    if (posts.length) accounts.push({handle, posts});
  }
  if (!accounts.length) throw new Error('候補投稿を取得できませんでした。ログイン状態またはThreads画面を確認してください。');
  await api(`/mixpost/collector/threads/${job.uuid}/complete`, {captured_at:new Date().toISOString(),accounts});
  console.log(`${accounts.length}アカウントの調査を完了しました。`);
} catch (error) {
  console.error(error.message);
  if (job?.uuid) await api(`/mixpost/collector/threads/${job.uuid}/fail`, {message:error.message}).catch(()=>{});
  process.exitCode = 1;
} finally { await context.close(); }
