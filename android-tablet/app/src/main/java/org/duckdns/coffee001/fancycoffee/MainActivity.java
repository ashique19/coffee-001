package org.duckdns.coffee001.fancycoffee;

import android.annotation.SuppressLint;
import android.app.DownloadManager;
import android.content.Context;
import android.content.Intent;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.os.Message;
import android.print.PrintAttributes;
import android.print.PrintDocumentAdapter;
import android.print.PrintManager;
import android.view.View;
import android.view.WindowManager;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.OnBackPressedCallback;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.core.view.WindowInsetsControllerCompat;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import java.util.Locale;

public class MainActivity extends AppCompatActivity {

    public static final String START_URL = "https://coffee-001.duckdns.org/login";
    public static final String ALLOWED_HOST = "coffee-001.duckdns.org";

    private static final String PRINT_HOOK_JS =
            "(function(){"
                    + "if(window.__healthyBrewPrintHooked)return;"
                    + "window.__healthyBrewPrintHooked=true;"
                    + "window.print=function(){"
                    + "if(window.HealthyBrew&&window.HealthyBrew.printPage){"
                    + "window.HealthyBrew.printPage();"
                    + "}"
                    + "};"
                    + "})();";

    private WebView webView;
    private SwipeRefreshLayout swipeRefresh;
    private View errorPane;
    private TextView errorMessage;
    private ValueCallback<Uri[]> filePathCallback;
    private ActivityResultLauncher<Intent> fileChooserLauncher;
    private String lastFailedUrl = START_URL;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        setContentView(R.layout.activity_main);
        hideSystemBars();

        webView = findViewById(R.id.webView);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        errorPane = findViewById(R.id.errorPane);
        errorMessage = findViewById(R.id.errorMessage);
        Button retryButton = findViewById(R.id.retryButton);

        fileChooserLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    Uri[] uris = null;
                    if (result.getResultCode() == RESULT_OK && result.getData() != null) {
                        uris = WebChromeClient.FileChooserParams.parseResult(
                                result.getResultCode(), result.getData());
                        if (uris == null && result.getData().getData() != null) {
                            uris = new Uri[]{result.getData().getData()};
                        }
                    }
                    if (filePathCallback != null) {
                        filePathCallback.onReceiveValue(uris);
                        filePathCallback = null;
                    }
                });

        swipeRefresh.setColorSchemeResources(R.color.coffee_gold, R.color.coffee_cream);
        swipeRefresh.setProgressBackgroundColorSchemeResource(R.color.coffee_brown);
        swipeRefresh.setOnRefreshListener(this::reloadOrRetry);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) -> webView.getScrollY() > 0);

        retryButton.setOnClickListener(v -> retry());

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (errorPane.getVisibility() == View.VISIBLE) {
                    hideError();
                    if (webView.canGoBack()) {
                        webView.goBack();
                        return;
                    }
                }
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    setEnabled(false);
                    getOnBackPressedDispatcher().onBackPressed();
                }
            }
        });

        configureWebView();

        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState);
            lastFailedUrl = savedInstanceState.getString("lastFailedUrl", START_URL);
        } else if (!isOnline()) {
            showError(getString(R.string.error_offline), START_URL);
        } else {
            webView.loadUrl(START_URL);
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    private void configureWebView() {
        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setAcceptThirdPartyCookies(webView, true);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setSupportZoom(true);
        settings.setBuiltInZoomControls(true);
        settings.setDisplayZoomControls(false);
        settings.setSupportMultipleWindows(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setAllowFileAccess(false);
        settings.setAllowContentAccess(true);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setUserAgentString(settings.getUserAgentString() + " HealthyBrewTablet/1.1");

        webView.setWebViewClient(new StaffWebViewClient());
        webView.setWebChromeClient(new StaffChromeClient());
        webView.addJavascriptInterface(new PrintBridge(this), "HealthyBrew");
        webView.setDownloadListener(new StaffDownloadListener());
        webView.setBackgroundColor(getResources().getColor(R.color.coffee_brown, getTheme()));
    }

    public void printCurrentPage() {
        PrintManager printManager = (PrintManager) getSystemService(Context.PRINT_SERVICE);
        if (printManager == null) {
            return;
        }
        String jobName = getString(R.string.app_name);
        PrintDocumentAdapter adapter = webView.createPrintDocumentAdapter(jobName);
        printManager.print(jobName, adapter, new PrintAttributes.Builder().build());
    }

    private void reloadOrRetry() {
        if (errorPane.getVisibility() == View.VISIBLE) {
            retry();
            return;
        }
        if (!isOnline()) {
            swipeRefresh.setRefreshing(false);
            showError(getString(R.string.error_offline), currentOrStartUrl());
            return;
        }
        webView.reload();
    }

    private void retry() {
        String target = lastFailedUrl == null || lastFailedUrl.isEmpty() ? START_URL : lastFailedUrl;
        if (!isOnline()) {
            swipeRefresh.setRefreshing(false);
            showError(getString(R.string.error_offline), target);
            return;
        }
        hideError();
        swipeRefresh.setRefreshing(true);
        webView.loadUrl(target);
    }

    private void showError(String message, String failedUrl) {
        lastFailedUrl = failedUrl == null ? START_URL : failedUrl;
        errorMessage.setText(message);
        errorPane.setVisibility(View.VISIBLE);
        swipeRefresh.setRefreshing(false);
    }

    private void hideError() {
        errorPane.setVisibility(View.GONE);
    }

    private String currentOrStartUrl() {
        String url = webView.getUrl();
        return url == null || url.isEmpty() ? START_URL : url;
    }

    private boolean isAllowedHost(@Nullable Uri uri) {
        if (uri == null || uri.getHost() == null) {
            return false;
        }
        return ALLOWED_HOST.equalsIgnoreCase(uri.getHost());
    }

    private boolean handleUri(@NonNull Uri uri) {
        String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase(Locale.US);

        if ("tel".equals(scheme) || "mailto".equals(scheme) || "sms".equals(scheme)) {
            openExternal(uri);
            return true;
        }

        if ("http".equals(scheme)) {
            Toast.makeText(this, R.string.https_only, Toast.LENGTH_SHORT).show();
            return true;
        }

        if ("https".equals(scheme) && isAllowedHost(uri)) {
            return false;
        }

        if ("https".equals(scheme) || "intent".equals(scheme)) {
            openExternal(uri);
            return true;
        }

        return true;
    }

    private void openExternal(@NonNull Uri uri) {
        try {
            Intent intent;
            if ("intent".equalsIgnoreCase(uri.getScheme())) {
                intent = Intent.parseUri(uri.toString(), Intent.URI_INTENT_SCHEME);
            } else {
                intent = new Intent(Intent.ACTION_VIEW, uri);
            }
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            startActivity(intent);
        } catch (Exception ignored) {
            Toast.makeText(this, R.string.cannot_open_link, Toast.LENGTH_SHORT).show();
        }
    }

    private boolean isOnline() {
        ConnectivityManager cm = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        if (cm == null) {
            return true;
        }
        Network network = cm.getActiveNetwork();
        if (network == null) {
            return false;
        }
        NetworkCapabilities caps = cm.getNetworkCapabilities(network);
        return caps != null && (
                caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI)
                        || caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)
                        || caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
        );
    }

    private void hideSystemBars() {
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        WindowInsetsControllerCompat controller =
                WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView());
        controller.hide(WindowInsetsCompat.Type.systemBars());
        controller.setSystemBarsBehavior(
                WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
    }

    private void injectPrintHook() {
        String url = webView.getUrl();
        if (url == null || !url.startsWith("https://" + ALLOWED_HOST)) {
            return;
        }
        webView.evaluateJavascript(PRINT_HOOK_JS, null);
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) {
            hideSystemBars();
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        webView.onPause();
        CookieManager.getInstance().flush();
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
        hideSystemBars();
    }

    @Override
    protected void onDestroy() {
        CookieManager.getInstance().flush();
        webView.destroy();
        super.onDestroy();
    }

    @Override
    protected void onSaveInstanceState(@NonNull Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
        outState.putString("lastFailedUrl", lastFailedUrl);
    }

    private class StaffWebViewClient extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading(@NonNull WebView view, @NonNull WebResourceRequest request) {
            if (!request.isForMainFrame()) {
                return false;
            }
            return handleUri(request.getUrl());
        }

        @Override
        public void onPageFinished(@NonNull WebView view, @NonNull String url) {
            swipeRefresh.setRefreshing(false);
            hideError();
            injectPrintHook();
            CookieManager.getInstance().flush();
        }

        @Override
        public void onReceivedError(
                @NonNull WebView view,
                @NonNull WebResourceRequest request,
                @NonNull WebResourceError error) {
            if (!request.isForMainFrame()) {
                return;
            }
            String url = request.getUrl() == null ? currentOrStartUrl() : request.getUrl().toString();
            boolean offline = !isOnline()
                    || error.getErrorCode() == WebViewClient.ERROR_HOST_LOOKUP
                    || error.getErrorCode() == WebViewClient.ERROR_CONNECT
                    || error.getErrorCode() == WebViewClient.ERROR_TIMEOUT
                    || error.getErrorCode() == WebViewClient.ERROR_PROXY_AUTHENTICATION
                    || error.getErrorCode() == WebViewClient.ERROR_IO;
            showError(
                    getString(offline ? R.string.error_offline : R.string.error_generic),
                    url);
        }
    }

    private class StaffChromeClient extends WebChromeClient {
        @Override
        public boolean onShowFileChooser(
                WebView webView,
                ValueCallback<Uri[]> filePathCallback,
                FileChooserParams fileChooserParams) {
            if (MainActivity.this.filePathCallback != null) {
                MainActivity.this.filePathCallback.onReceiveValue(null);
            }
            MainActivity.this.filePathCallback = filePathCallback;
            Intent intent = fileChooserParams.createIntent();
            try {
                fileChooserLauncher.launch(intent);
                return true;
            } catch (Exception ignored) {
                Intent fallback = new Intent(Intent.ACTION_GET_CONTENT);
                fallback.addCategory(Intent.CATEGORY_OPENABLE);
                fallback.setType("*/*");
                try {
                    fileChooserLauncher.launch(Intent.createChooser(fallback, getString(R.string.app_name)));
                    return true;
                } catch (Exception alsoIgnored) {
                    MainActivity.this.filePathCallback = null;
                    return false;
                }
            }
        }

        @Override
        public boolean onCreateWindow(
                WebView view, boolean isDialog, boolean isUserGesture, Message resultMsg) {
            WebView temp = new WebView(view.getContext());
            temp.setWebViewClient(new WebViewClient() {
                @Override
                public boolean shouldOverrideUrlLoading(
                        @NonNull WebView v, @NonNull WebResourceRequest request) {
                    Uri uri = request.getUrl();
                    if (!handleUri(uri)) {
                        webView.loadUrl(uri.toString());
                    }
                    return true;
                }
            });
            WebView.WebViewTransport transport = (WebView.WebViewTransport) resultMsg.obj;
            transport.setWebView(temp);
            resultMsg.sendToTarget();
            return true;
        }
    }

    private class StaffDownloadListener implements DownloadListener {
        @Override
        public void onDownloadStart(
                String url,
                String userAgent,
                String contentDisposition,
                String mimeType,
                long contentLength) {
            Uri uri = Uri.parse(url);
            if (!"https".equalsIgnoreCase(uri.getScheme()) || !isAllowedHost(uri)) {
                openExternal(uri);
                return;
            }
            try {
                DownloadManager.Request request = new DownloadManager.Request(uri);
                request.setMimeType(mimeType);
                String cookies = CookieManager.getInstance().getCookie(url);
                if (cookies != null) {
                    request.addRequestHeader("Cookie", cookies);
                }
                request.addRequestHeader("User-Agent", userAgent);
                request.setNotificationVisibility(
                        DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                String name = URLUtil.guessFileName(url, contentDisposition, mimeType);
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
                DownloadManager manager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                if (manager != null) {
                    manager.enqueue(request);
                    Toast.makeText(MainActivity.this, R.string.download_started, Toast.LENGTH_SHORT).show();
                }
            } catch (Exception ignored) {
                openExternal(uri);
            }
        }
    }
}
