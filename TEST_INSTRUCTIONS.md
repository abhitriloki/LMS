# Diagnostic Test Instructions

## Current Issue
- Pages load but show blank content
- Sidebar not visible
- Main content area empty

## Tests to Run

### Test 1: Check Alpine.js
Go to: http://127.0.0.1:8000/test-alpine.html

**Expected:**
- Should see "Alpine.js is working!"
- Toggle button should show/hide text
- Dark mode toggle should work

**If this works:** Alpine.js is fine, issue is elsewhere
**If this doesn't work:** Alpine.js isn't loading

### Test 2: Check Browser Console
1. Press F12
2. Go to Console tab
3. Copy ALL red errors (not just favicon)
4. Share with me

### Test 3: Check Network Tab
1. Press F12
2. Go to Network tab
3. Refresh page (Ctrl+R)
4. Look for any RED items (failed requests)
5. Tell me which files are failing

### Test 4: Check if Vite is Running
In terminal, check if you see:
```
VITE v5.x.x  ready in xxx ms
```

**If NOT running:**
```bash
npm run dev
```

Keep this terminal open while testing.

## Common Issues

### Issue 1: Vite Not Running
**Solution:** Run `npm run dev` in a separate terminal

### Issue 2: Assets Not Built
**Solution:** Run `npm run build`

### Issue 3: Cache Issues
**Solution:**
```bash
php artisan optimize:clear
php artisan view:clear
```

### Issue 4: Alpine.js Not Loading
**Check:** Browser console for JavaScript errors

## What to Share

Please share:
1. ✅ Result of Alpine test page
2. ✅ ALL console errors (F12 → Console)
3. ✅ Network tab - any 404/500 errors
4. ✅ Is `npm run dev` or `npm run build` running?

