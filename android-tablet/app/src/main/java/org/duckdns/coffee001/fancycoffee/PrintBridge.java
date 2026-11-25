package org.duckdns.coffee001.fancycoffee;

import android.webkit.JavascriptInterface;

/**
 * Lets the site's window.print() open the Android print sheet for invoices.
 */
public class PrintBridge {
    private final MainActivity activity;

    public PrintBridge(MainActivity activity) {
        this.activity = activity;
    }

    @JavascriptInterface
    public void printPage() {
        activity.runOnUiThread(activity::printCurrentPage);
    }
}
