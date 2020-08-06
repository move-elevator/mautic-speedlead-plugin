<?php
// Extend the base content
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('headerTitle', $view['translator']->trans('mautic.speedlead.header_title'))
?>

<div class="speedlead-content">
    <?php
        if (false === empty($message)) {
            echo $message;
        }

        if (false === empty($reports)) {
            $outputString = $view['translator']->trans('mautic.speedlead.import_finished', ['%reportCount%' => count($reports)]);
        }
    ?>
    <div style="margin-left: 2rem;"><?php
        if (false === empty($form)) {
          echo $view['form']->form($form);
        }
    ?></div>
    <br />
    <div style="margin-left: 2rem;"><?php if (false === empty($outputString)) {echo $outputString;} else {echo '';} ?></div>
</div>
