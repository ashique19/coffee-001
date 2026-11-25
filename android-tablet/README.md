# Healthy Brew tablet app

Android WebView wrapper for the Healthy Brew staff area. Built for a Samsung Galaxy Tab A9 (8.7") used in the shop. It opens the staff login full-screen, stays logged in via cookies, and supports invoice printing, file uploads, pull-to-refresh, and a simple offline retry screen.

- **Start URL:** https://coffee-001.duckdns.org/login
- Logged-in staff are redirected to `/admin/dashboard`.
- **App name:** Healthy Brew (Arabic label: هيلثي برو)
- **Package:** `org.duckdns.coffee001.fancycoffee` (unchanged, so this build installs over the previous v1.0 app)
- **Version:** 1.1 (versionCode 2), minSdk 24, targetSdk 34
- Navigation stays on `coffee-001.duckdns.org` (HTTPS only). Other links open in the system browser.

This folder is only the tablet app. It is not under `public/`, so a normal site deploy will not publish it to the café website.

## Install on the tablet

1. On the Tab A9, open the v1.1 APK in this folder:
   [releases/healthy-brew-tablet-v1.1.apk](releases/healthy-brew-tablet-v1.1.apk)
2. If Android asks, allow install from that source (Files, Chrome, or Drive).
3. If the older Fancy Coffee app is already installed, this file updates it in place (same package and signing key). The home-screen name becomes **Healthy Brew**.
4. Open **Healthy Brew** (Arabic label: هيلثي برو) and sign in with a staff account.
5. Keep **Android System WebView** up to date.

The signed APK is about 4.3 MB. This is a sideload install, not a Play Store listing. Later updates must be signed with the same release key, or the tablet will refuse the install.

## Rebuild

You need JDK 17 or newer and an Android SDK with `platforms;android-34` and `build-tools;34.0.0`.

```bash
cd android-tablet
cp local.properties.example local.properties
# Set sdk.dir to your Android SDK path, or export ANDROID_HOME
./gradlew :app:assembleRelease
```

The signed APK is written to `app/build/outputs/apk/release/app-release.apk`.

**Release keystore:** the signing key is kept off this repo, on the bot box at `/workspace/coffee-tablet-apk-keys/`. Do not commit `keystore.properties`, `.jks`, or key passwords. To sign a release on another machine, create a gitignored `android-tablet/keystore.properties` with `storeFile`, `storePassword`, `keyAlias`, and `keyPassword`. On the bot box, a rebuild can use the existing off-repo keystore automatically. Debug builds do not need it.

## Notes

- The screen stays on while the app is open (shop use). Swipe from the edge to show system bars.
- Cookies persist across app restarts, but the website session may still expire (often after a couple of hours).
- Invoice `window.print()` uses the Android print sheet. Set up a printer on the tablet, or save as PDF.
