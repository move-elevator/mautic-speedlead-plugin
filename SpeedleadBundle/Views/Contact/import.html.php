<?php
// Extend the base content
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('headerTitle', 'speedlead-import')
?>

<div class="speedlead-content">
    <?php
        if (false === empty($message)) {
            echo $message;
        }

        if (null !== $reports) {
            $outputString = sprintf('handled %s report/s.', count($reports));
        }
    ?>
    <div style="margin-left: 2rem;"><?php if ($outputString) {echo $outputString;} else {echo '';} ?></div>
</div>
