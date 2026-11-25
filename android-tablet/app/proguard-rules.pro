# Release builds are not minified. Keep the print JS interface if minify is turned on later.
-keepclassmembers class org.duckdns.coffee001.fancycoffee.PrintBridge {
    @android.webkit.JavascriptInterface <methods>;
}
