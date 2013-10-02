<?php
require_once(dirname(__FILE__) . '/../../../config.php');
header("refresh:1; url=" . required_param('returnurl', PARAM_URL));
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-control: post-check=0, pre-check=0, false");
header("Content-Type: application/x-java-jnlp-file");
header('Content-Disposition: attachment; filename="bbl.jnlp"');
header("Pragma: no-cache");
?>
<jnlp spec="1.0+" 
      codebase="<?php echo required_param('codebase', PARAM_URL); ?>" 
      >
    <information>
        <title>Behavioral Biometrics Logger</title>
        <vendor>Vinnie Monaco</vendor>
    </information>
    <resources>
        <!-- Application Resources -->
        <j2se version="1.6+"
              href="http://java.sun.com/products/autodl/j2se"/>
        <jar href="bbl.jar" main="true" />
        <jar href="httpclient-4.2.3.jar" />
        <jar href="httpcore-4.2.2.jar" />
        <jar href="httpmime-4.2.3.jar" />
        <jar href="JNativeHook.jar" />
        <jar href="ws-commons-util-1.0.2.jar" />
        <jar href="commons-logging-1.1.1.jar" />
        <jar href="json-simple-1.1.1.jar" />
    </resources>
    <application-desc
        name="Behavioral Biometrics Logger"
        main-class="com.vmonaco.bbl.BioLogger"
        width="300"
        height="300">
        <argument><?php echo required_param('enrollurl', PARAM_URL); ?></argument>
        <argument><?php echo required_param('username', PARAM_USERNAME); ?></argument>
        <argument><?php echo required_param('task', PARAM_TEXT); ?></argument>
        <?php if ($tags = optional_param('tags', '', PARAM_TEXT)) {
                echo "<argument>$tags</argument>\n";
              } ?>
    </application-desc>
    <update check="timeout" policy="always" />
    <security><all-permissions/></security>
</jnlp>