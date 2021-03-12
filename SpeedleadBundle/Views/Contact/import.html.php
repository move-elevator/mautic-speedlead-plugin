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
          $surveyFields = [];

          echo $view['form']->start($form);
          echo $view['form']->row($form->children['createdBefore']);
          echo $view['form']->row($form->children['updatedAfter']);
    ?>
          <h3> <?php echo $view['translator']->trans('mautic.speedlead.import_survey'); ?></h3>
          <br />

          <p><?php echo $view['translator']->trans('mautic.speedlead.import_survey.info'); ?></p>
          <br />

          <p>
            <?php if (true === $isAutomaticImportEnabled) {
                echo $view['translator']->trans('mautic.speedlead.import_survey.automatic_import_enabled');
            } else {
                echo $view['translator']->trans('mautic.speedlead.import_survey.automatic_import_disabled');
            } ?>
          </p>
          <br />

          <button>
            <a href="<?php echo $view['router']->url('mautic_speedlead_contact_survey_configuration_update'); ?>" data-toggle="ajax">
              <?php echo $view['translator']->trans('mautic.speedlead.update_survey') ?>
            </a>
          </button>
          <span style="font-style: italic"><?php echo $view['translator']->trans('mautic.speedlead.update_survey.info'); ?></span>
          <br />
          <br />
    <?php
          foreach ($form->children as $formKey => $formChild) {
              if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $formKey)) {
                  echo $view['form']->row($form->children[$formKey]);
              }

              if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}_\d+$/', $formKey)) {
              ?>
                  <div style="margin-left:2rem">
                    <?php echo $view['form']->row($form->children[$formKey]); ?>
                  </div>

              <?php
              }
          }

          echo $view['form']->end($form);
        }
    ?>
    </div>
    <br />
    <div style="margin-left: 2rem;"><?php if (false === empty($outputString)) {echo $outputString;} else {echo '';} ?></div>
</div>
