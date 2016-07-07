<?php

namespace Drupal\auto_screenshots\Tests;

use Drupal\Core\Site\Settings;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for tests that automate screenshots for the User Guide.
 *
 * To make a class for a new language:
 * - Extend this class.
 * - Override the $demoInput member variable, translating the input into the
 *   target language. Note that most of the text should not contain
 *   ' characters, as this will result in an error when generating the screen
 *   shots.
 *
 * The HTML output for eachq screenshot is manipulated using JavaScript, so that
 * it only shows a small area of the page, with the rest hidden. The script that
 * captures the images then trims the images automatically down to the relevant
 * area.
 *
 * See README.txt file in the module directory for instructions for making
 * screenshot images from this test output.
 */
abstract class UserGuideDemoTestBase extends WebTestBase {

  /**
   * Which Drupal Core software version to use for the downloading screenshots.
   */
  protected $latestRelease = '8.1.3';

  /**
   * Strings and other information to input into the demo site.
   *
   * This information is translated into other languages in the
   * specific-language test classes.
   *
   * @var array
   */
  protected $demoInput = [
    // Default and second languages for the site.
    'first_langcode' => 'en',
    'second_langcode' => 'es',

    // Basic site information.
    'site_name' => 'Anytown Farmers Market',
    'site_slogan' => 'Farm Fresh Food',
    'site_mail' => 'info@example.com',
    'site_default_country' => 'US',
    'date_default_timezone' => 'America/Los_Angeles',

    // Home page content item.
    'home_title' => 'Home',
    'home_body' => "<p>Welcome to City Market - your neighborhood farmers market!</p>\n<p>Open: Sundays, 9 AM to 2 PM, April to September</p>\n<p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_summary' => 'Opening times and location of City Market',
    'home_path' => '/home',
    'home_revision_log_message' => 'Updated opening hours',

    // About page content item.
    'about_title' => 'About',
    'about_body' => "<p>City Market started in April 1990 with five vendors.</p>\n<p>Today, it has 100 vendors and an average of 2000 visitors per day.</p>",
    'about_path' => '/about',
    'about_description' => 'History of the market',

    // Vendor content type settings.
    'vendor_type_name' => 'Vendor',
    'vendor_type_description' => 'Information about a vendor',
    'vendor_type_title_label' => 'Vendor name',
    'vendor_field_url_label' => 'Vendor URL',
    'vendor_field_image_label' => 'Main image',
    'vendor_field_image_directory' => 'vendors',

    // Vendor 1 content item.
    'vendor_1_title' => 'Happy Farm',
    'vendor_1_path' => '/vendors/happy_farm',
    'vendor_1_summary' => 'Happy Farm grows vegetables that you will love.',
    'vendor_1_body' => '<p>Happy Farm grows vegetables that you will love.</p>\n
<p>We grow tomatoes, carrots, and beets, as well as a variety of salad greens.</p>',
    'vendor_1_url' => 'http://happyfarm.com',

    // Vendor 2 content item.
    'vendor_2_title' => 'Sweet Honey',
    'vendor_2_path' => '/vendors/sweet_honey',
    'vendor_2_summary' => 'Sweet Honey produces honey in a variety of flavors throughout the year.',
    'vendor_2_body' => '<p>Sweet Honey produces honey in a variety of flavors throughout the year.</p>\n<p>Our varieties include clover, apple blossom, and strawberry.</p>',
    'vendor_2_url' => 'http://sweethoney.com',

    // Recipe content type settings.
    'recipe_type_name' => 'Recipe',
    'recipe_type_description' => 'Recipe submitted by a vendor',
    'recipe_type_title_label' => 'Recipe name',
    'recipe_field_image_label' => 'Main image',
    'recipe_field_image_directory' => 'recipes',
    'recipe_field_ingredients_label' => 'Ingredients',
    'recipe_field_ingredients_help' => 'Enter ingredients that site visitors might want to search for',
    'recipe_field_submitted_label' => 'Submitted By',
    'recipe_field_submitted_help' => 'Choose the vendor that submitted this recipe',

    // Recipe ingredients terms added.
    'recipe_field_ingredients_term_1' => 'Butter',
    'recipe_field_ingredients_term_2' => 'Eggs',
    'recipe_field_ingredients_term_3' => 'Milk',
  ];

  /**
   * For our demo site, start with the standard profile install.
   */
  protected $profile = 'standard';

  /**
   * Modules needed for this test.
   */
  public static $modules = ['update'];

  /**
   * We need verbose logging to be on.
   */
  public $verbose = TRUE;

  /**
   * Counter for screenshot output, separate from regular verbose IDs.
   */
  protected $screenshotId = 0;

  /**
   * Builds the entire demo site and makes screenshots.
   *
   * Note that the method name starts with "test" so that it will be detected
   * as a "test" to run, in the specific-language classes.
   */
  public function testBuildDemoSite() {
    $this->drupalLogin($this->rootUser);

    // Add the first language, set the default language to that, and delete
    // English, to simulate having installed in a different language. No
    // screen shots for this!
    if ($this->demoInput['first_langcode'] != 'en') {
      // Note that the buttons should still be in English until after
      // the other language is set as the default language.
      // Turn on the language and locale modules.
      $this->drupalGet('admin/modules');
      $this->drupalPostForm(NULL, [
          'modules[Multilingual][language][enable]' => TRUE,
          'modules[Multilingual][locale][enable]' => TRUE,
        ], 'Install');

      // Add the other language.
      $this->drupalPostForm('admin/config/regional/language/add', [
          'predefined_langcode' => $this->demoInput['first_langcode'],
        ], 'Add language');
      // Set it to default. After this, the buttons should be translated.
      $this->drupalPostForm('admin/config/regional/language', [
          'site_default_language' => $this->demoInput['first_langcode'],
        ], 'Save configuration');
      // Delete English.
      $this->drupalPostForm('admin/config/regional/language/delete/en', [], $this->callT('Delete'));
    }

    // Figure out where the assets directory is.
    $dir_parts = explode('/', drupal_get_path('module', 'auto_screenshots'));
    array_pop($dir_parts);
    $assets_directory = implode('/', $dir_parts) . '/assets/';

    // Topic: preface-conventions: Conventions of the user guide.
    $this->drupalGet('admin/config');
    // Top navigation bar on any admin page, with Manage menu showing.
    // This same screenshot is also config-overview-toolbar.png in the
    // config-overview topic.
    $this->setUpScreenShot('preface-conventions-top-menu.png', 'onLoad="' . $this->addBorder('#toolbar-bar', '#ffffff') . $this->hideArea('header, .region-breadcrumb, .page-content') . $this->setBodyColor() . '"');

    // System section of admin/config page.
    $this->setUpScreenShot('preface-conventions-config-system.png', 'onLoad="' . $this->showOnly('.layout-column:odd .panel:first') . '"');

    // Topic: block-regions - postpone until after theme is configured.

    // Topic: install-prepare - Preparing to install.
    $this->drupalGet('https://www.drupal.org/download');
    // Main area of https://www.drupal.org/download.
    $this->setUpScreenShot('install-prepare-downloads.png', 'onLoad="' . $this->hideArea('#nav-header') . $this->hideArea('#header') . $this->hideArea('.drupal-modules') . $this->hideArea('.drupal-modules-facets') . $this->hideArea('#footer') . '"');
    $this->drupalGet('https://www.drupal.org/project/drupal');
    // Recommended releases section of https://www.drupal.org/project/drupal.
    $this->setUpScreenShot('install-prepare-recommended.png', 'onLoad="' . $this->showOnly('#node-3060 .content') . $this->hideArea('.field-name-body') . $this->hideArea('.pane-project-downloads-development') . $this->hideArea('.pane-custom') . $this->hideArea('.pane-project-downloads-other') . $this->hideArea('.pane-download-releases-link') . '"');
    $this->drupalGet('https://www.drupal.org/project/drupal/releases/' . $this->latestRelease);
    // File section of a recent Drupal release download page, such as
    // https://www.drupal.org/project/drupal/releases/8.1.3.
    $this->setUpScreenShot('install-prepare-files.png', 'onLoad="' . $this->showOnly('#page-inner') . $this->hideArea('#page-title-tools') . $this->hideArea('#nav-content') . $this->hideArea('.panel-display .content') . $this->hideArea('.panel-display .footer') . $this->hideArea('.views-field-field-release-file-hash') . $this->hideArea('.views-field-field-release-file-sha1') . $this->hideArea('.views-field-field-release-file-sha256') . '"');

    // Topic install-run - Running the installer. Skip -- manual screenshots.

    // Topic: config-overview - Concept: Administrative overview.
    $this->drupalGet('admin/config');
    // Top navigation bar on any admin page, with Manage menu showing.
    // Same as preface-conventions-top-menu.png defined earlier.
    $this->setUpScreenShot('config-overview-toolbar.png', 'onLoad="' . $this->addBorder('#toolbar-bar', '#ffffff') . $this->hideArea('header, .region-breadcrumb, .page-content') . $this->setBodyColor() . '"');

    // The vertical orientation navigation screenshot could not be
    // successfully reproduced, unfortunately -- the buttons didn't show up.
    // So config-overview-vertical.png must be done manually.

    // Defer config-overview-pencils.png until after theme is configured.

    // Topic: config-basic - Editing basic site information.
    $this->drupalGet('admin/config/system/site-information');
    $this->drupalPostForm(NULL, [
        'site_name' => $this->demoInput['site_name'],
        'site_slogan' => $this->demoInput['site_slogan'],
        'site_mail' => $this->demoInput['site_mail'],
      ], $this->callT('Save configuration'));

    // In this case, we want the screen shot made after we have entered the
    // information, because for a normal user, this information would have
    // been set up during the install.
    $this->drupalGet('admin/config/system/site-information');
    // Site details section of admin/config/system/site-information.
    $this->setUpScreenShot('config-basic-SiteInfo.png', 'onLoad="' . $this->showOnly('#edit-site-information') . $this->setWidth('#edit-site-information') . '"');

    $this->drupalGet('admin/config/regional/settings');
    $this->drupalPostForm(NULL, [
      'site_default_country' => $this->demoInput['site_default_country'],
      'date_default_timezone' => $this->demoInput['date_default_timezone'],
      'configurable_timezones' => FALSE,
      ], $this->callT('Save configuration'));

    $this->drupalGet('admin/config/regional/settings');
    // Locale and Time Zones sections of admin/config/regional/settings.
    $this->setUpScreenShot('config-basic-TimeZone.png', 'onLoad="' . $this->showOnly('.page-content') . $this->setWidth('#edit-locale') . $this->setWidth('#edit-timezone') . '"');

    // Topic: config-install -- Installing a module.
    $this->drupalGet('admin/modules');
    // Top part of Core section of admin/modules, with Activity Tracker checked.
    $this->setUpScreenShot('config-install-check-modules.png', 'onLoad="jQuery(\'#edit-modules-core-tracker-enable\').attr(\'checked\', 1);' . $this->hideArea('#toolbar-administration, header, .region-pre-content, .region-highlighted, .help, .action-links, .region-breadcrumb, #edit-filters, #edit-actions') . $this->hideArea('#edit-modules-core-experimental, #edit-modules-field-types, #edit-modules-multilingual, #edit-modules-other, #edit-modules-testing, #edit-modules-web-services') . $this->hideArea('#edit-modules-core table tbody tr:gt(4)') . '"');
    $this->drupalPostForm(NULL, [
        'modules[Core][tracker][enable]' => TRUE,
      ], $this->callT('Install'));

    // Topic: config-uninstall - Uninstalling unused modules.
    $this->drupalGet('admin/modules/uninstall');
    // Top part of admin/modules/uninstall, with Activity Tracker checked.
    $this->setUpScreenShot('config-uninstall_check-modules.png', 'onLoad="jQuery(\'#edit-uninstall-tracker\').attr(\'checked\', 1); ' . $this->showOnly('table thead, table tbody tr:lt(4)') . '"');
    $this->drupalPostForm(NULL, [
        'uninstall[tracker]' => TRUE,
        'uninstall[search]' => TRUE,
        'uninstall[history]' => TRUE,
      ], $this->callT('Uninstall'));
    // Uninstall confirmation screen, after checking Activity Tracker, History,
    // and Search modules from admin/modules/uninstall.
    $this->setUpScreenShot('config-uninstall_confirmUninstall.png', 'onLoad="' . $this->hideArea('#toolbar-administration') . $this->setWidth('.block-system-main-block') . $this->setWidth('header', 640) . '"');
    $this->drupalPostForm(NULL, [], $this->callT('Uninstall'));

    // Topic config-user: Configuring user account settings.
    $this->drupalGet('admin/config/people/accounts');
    $this->drupalPostForm(NULL, [
        'user_register' => 'admin_only',
      ], $this->callT('Save configuration'));
    // Registration and cancellation section of admin/config/people/accounts.
    $this->setUpScreenShot('config-user_account_reg.png', 'onLoad="window.scroll(0,500);' . $this->showOnly('#edit-registration-cancellation') . $this->setWidth('#edit-registration-cancellation') . '"');
    // Emails section of admin/config/people/accounts.
    $this->setUpScreenShot('config-user_email.png', 'onLoad="window.scroll(0,5000); ' . $this->showOnly('div.form-type-vertical-tabs') . $this->hideArea('div.form-type-vertical-tabs details:gt(0)') . '"');

    // Topic config-theme: Configuring the theme.
    $this->drupalGet('admin/appearance');
    // Bartik section of admin/appearance.
    $this->setUpScreenShot('config-theme_bartik_settings.png', 'onLoad="' . $this->showOnly('.system-themes-list-installed') . $this->hideArea('.theme-admin') . '"');
    $this->drupalGet('admin/appearance/settings/bartik');
    // For this screenshot, before the setting are changed, use JavaScript to
    // scroll down to the bottom, uncheck Use the default logo, and outline
    // the logo upload box.
    // Logo upload section of admin/appearance/settings/bartik.
    $this->setUpScreenShot('config-theme_logo_upload.png', 'onLoad="window.scroll(0,6000); jQuery(\'#edit-default-logo\').click(); ' . $this->addBorder('#edit-logo-upload') . $this->showOnly('#edit-logo') . $this->setWidth('#edit-logo') . '"');

    $this->drupalPostForm(NULL, [
        'scheme' => '',
        'palette[top]' => '#7db84a',
        'palette[bottom]' => '#2a3524',
        'palette[bg]' => '#ffffff',
        'palette[sidebar]' => '#f8bc65',
        'palette[sidebarborders]' => '#e96b3c',
        'palette[footer]' => '#2a3524',
        'palette[titleslogan]' => '#ffffff',
        'palette[text]' => '#000000',
        'palette[link]' => '#2a3524',
        'default_logo' => FALSE,
        'logo_path' => $assets_directory . 'AnytownFarmersMarket.png',
      ], $this->callT('Save configuration'));

    $this->drupalGet('admin/appearance/settings/bartik');
    // Color settings section of admin/appearance/settings/bartik.
    $this->setUpScreenShot('config-theme_color_scheme.png', 'onLoad="window.scroll(0,200);' . $this->showOnly('#color_scheme_form') . $this->hideArea('h2') . $this->hideArea('.color-preview') . $this->setWidth('#color_scheme_form', 800) . '"');
    // Preview section of admin/appearance/settings/bartik.
    $this->setUpScreenShot('config-theme_color_scheme_preview.png', 'onLoad="window.scroll(0,1000);' . $this->showOnly('.color-preview') . $this->setWidth('#color_scheme_form', 700) . $this->removeScrollbars() . '"');

    $this->drupalGet('<front>');
    // Home page after theme settings are finished.
    $this->setUpScreenShot('config-theme_final_result.png', 'onLoad="' . $this->hideArea('#toolbar-administration, .contextual') . $this->removeScrollbars() . '"');

    // Back to topic: block-regions.
    $this->drupalGet('admin/structure/block/demo/bartik');
    // Bartik theme region preview at admin/structure/block/demo/bartik,
    // after configuring the theme for the Farmers Market scenario.
    $this->setUpScreenShot('block-regions-bartik.png', 'onLoad="' . $this->showOnly('#page-wrapper') . $this->removeScrollbars() . '"');

    // Back to screen shot: config-overview-pencils.jpg.
    // Home page with pencil icons showing, with configured theme.
    $this->drupalGet('<front>');
    $this->setUpScreenShot('config-overview-pencils.png', 'onLoad="' . $this->hideArea('footer') . $this->removeScrollbars() . $this->setBodyColor() . 'jQuery(\'.contextual\').show();' . '"');

    // Topic: content-create - Creating a Content Item
    // Create a Home page.
    $this->drupalGet('node/add/page');
    // General note: I could not get jQuery to fill in the text in
    // any textarea fields, using a variety of methods. So I think we will
    // need to live without these, alas! Also, some of the fields are
    // transformed after page load into editors, which competes with our basic
    // JavaScript.
    // Hm, .append seems to work...2
    // Partly filled-in node/add/page, with Summary section open.
    $this->setUpScreenShot('content-create-create-basic-page.png', 'onLoad="jQuery(\'#edit-title-0-value\').val(\'' . $this->demoInput['home_title'] . '\'); jQuery(\'#edit-path-settings, #edit-path-settings .details-wrapper\').show(); jQuery(\'#edit-path-0-alias\').val(\'' . $this->demoInput['home_path'] . '\');' . $this->hideArea('#toolbar-administration') . 'jQuery(\'.link-edit-summary\').click(); jQuery(\'.form-item-body-0-summary\').show();' . 'jQuery(\'#edit-body-0-summary\').append(\'' . $this->demoInput['home_summary'] . '\');' . '"');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['home_title'],
        'body[0][value]' => $this->demoInput['home_body'],
        'path[0][alias]' => $this->demoInput['home_path'],
      ], $this->callT('Save and publish'));

    // Create About page. No screenshots.
    $this->drupalPostForm('node/add/page', [
        'title[0][value]' => $this->demoInput['about_title'],
        'body[0][value]' => $this->demoInput['about_body'],
        'path[0][alias]' => $this->demoInput['about_path'],
      ], $this->callT('Save and publish'));

    // Topic: content-edit - Editing a content item
    $this->drupalGet('admin/content');
    // Content list on admin/content, with filters above.
    $this->setUpScreenShot('content-edit-admin-content.png', 'onLoad="' . $this->showOnly('.block-system-main-block') . $this->hideArea('.secondary-action') . $this->setBodyColor() . '"');
    $this->drupalGet('node/1/edit');
    // Revision area of the content node edit page.
    $this->setUpScreenShot('content-edit-revision.png', 'onLoad="' . $this->showOnly('#edit-meta') . 'jQuery(\'#edit-revision\').attr(\'checked\', 1); jQuery(\'#edit-revision-log-0-value\').append(\'' . $this->demoInput['home_revision_log_message'] . '\');' . '"');
    // Submit the revision.
    $this->drupalPostForm(NULL, [
        'revision_log[0][value]' => $this->demoInput['home_revision_log_message'],
      ], $this->callT('Save and keep published'));
    // Updated content message.
    $this->setUpScreenShot('content-edit-message.png', 'onLoad="' . $this->showOnly('.highlighted') . $this->setWidth('.highlighted') . $this->setBodyColor() . $this->removeScrollbars() . '"');

    // Topic: content-in-place-edit - it does not seem possible to make these
    // screenshots automatically. Skip.

    // Topic: menu-home - Designating a Front Page for your Site
    $this->drupalGet('admin/config/system/site-information');
    $this->drupalPostForm(NULL, [
        'site_frontpage' => '/home',
      ], $this->callT('Save configuration'));
    // Fix the prefix showing the site URL to say example.com.
    // Front page section of admin/config/system/site-information.
    $this->setUpScreenShot('menu-home_new_text_field.png', 'onLoad="' . $this->showOnly('#edit-front-page') . $this->setWidth('#edit-front-page') . 'jQuery(\'.form-item-site-frontpage .field-prefix\').text(\'http://example.com\');' . '"');

    $this->drupalGet('<front>');
    // Site front page after configuring it to point to the Home content item.
    $this->setUpScreenShot('menu-home_final.png', 'onLoad="' . $this->hideArea('#toolbar-administration, footer, .contextual') . $this->setBodyColor() . $this->removeScrollbars() . '"');

    // Topic: menu-link-from-content: Adding a page to the navigation.
    $this->drupalGet('admin/content');
    // Content table from admin/content page, with a red border around the Edit
    // button for the About page.
    $this->setUpScreenShot('menu-link-from-content_edit_page.png', 'onLoad="' . $this->showOnly('.views-table') . $this->addBorder('table.views-view-table tbody tr:last .dropbutton-widget') . $this->hideArea('.secondary-action') . '"');

    $this->drupalGet('node/2/edit');
    $this->drupalPostForm(NULL, [
        'menu[enabled]' => TRUE,
        'menu[title]' => $this->demoInput['about_title'],
        'menu[description]' => $this->demoInput['about_description'],
        'menu[weight]' => -2,
      ], $this->callT('Save and keep published'));
    $this->drupalGet('node/2/edit');
    // Menu settings section of content editing page.
    $this->setUpScreenShot('menu-link-from-content.png', 'onLoad="' . $this->showOnly('#edit-menu') . '"');
    $this->drupalGet('<front>');
    // Home page after adding About to the navigation.
    $this->setUpScreenShot('menu-link-from-content-result.png', 'onLoad="' . $this->hideArea('#toolbar-administration, .contextual, footer') . $this->setBodyColor() . $this->removeScrollbars() . '"');

    // Topic: menu-reorder - Changing the order of navigation.
    $this->drupalGet('admin/structure/menu');
    // Menu list section of admin/structure/menu, with Edit menu button on Main
    // navigation menu highlighted.
    $this->setUpScreenShot('menu-reorder_menu_titles.png', 'onLoad="' . $this->showOnly('table') . $this->addBorder('tr:eq(3) .dropbutton-widget') . $this->hideArea('.secondary-action') . '"');

    $this->drupalGet('admin/structure/menu/manage/main');
    // Menu links section of admin/structure/menu/manage/main.
    $this->setUpScreenShot('menu-reorder_edit_menu.png', 'onLoad="' . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, #block-seven-local-actions, .form-type-textfield, .tabledrag-toggle-weight') . $this->setWidth('table') . '"');

    // Simulating dragging on the ordering screen is a bit complex.
    // Menu links section of admin/structure/menu/manage/main, after
    // changing the order.
    $this->setUpScreenShot('menu-reorder_reorder.png', 'onLoad="' . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, #block-seven-local-actions, .form-type-textfield') . 'jQuery(\'table\').before(\'<div style=&quot;display: block; width: 600px;&quot; class=&quot;tabledrag-changed-warning messages messages--warning&quot; role=&quot;alert&quot;><abbr class=&quot;warning tabledrag-changed&quot;>*</abbr>' . $this->callT('You have unsaved changes.') . '</div>\');' . 'var r = jQuery(\'table tbody tr:last\').detach(); jQuery(\'table tbody\').prepend(r); jQuery(\'table tbody tr:first\').toggleClass(\'drag-previous\');' . $this->setWidth('table') . '"');

    // Actually figuring out what to submit on the editing page is difficult,
    // because the field name has some config hash in it. So instead, to make
    // the change in the test, go back to the about page and edit the weight
    // there.
    $this->drupalGet('node/2/edit');
    $this->drupalPostForm(NULL, [
        'menu[weight]' => 10,
      ], $this->callT('Save and keep published'));
    $this->drupalGet('<front>');
    // Header section of Home page with reordered menu items.
    $this->setUpScreenShot('menu-reorder_final_order.png', 'onLoad="' . $this->showOnly('header') . $this->hideArea('.visually-hidden, .contextual, .menu-toggle') . $this->setWidth('header') . $this->setBodyColor() . $this->removeScrollbars() . '"');

    // @todo Continue with JavaScript stuff from here...

    // Topic: structure-content-type - Adding a Content Type
    // Create the Vendor content type.
    $this->drupalGet('admin/structure/types');
    $this->drupalGet('admin/structure/types/add');
    // For this one, get the Name and Description fields, and the top of page.
    $this->setUpScreenShot('structure-content-type-add.png');
    // For this one, get just the Submission form settings section.
    $this->setUpScreenShot('structure-content-type-add-submission-form-settings.png');
    // For this one, expand Publishing options and check boxes.
    $this->setUpScreenShot('structure-content-type-add-Publishing-Options.png', 'onLoad="jQuery(\'#edit-workflow .details-wrapper).show(); jQuery(\'.vertical-tabs li:eq(0)).toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:eq(1)).toggleClass(\'is-selected\');" jQuery(\'#edit-options-promote\').attr(\'checked\', 0); jQuery(\'#edit-options-revision\').attr(\'checked\', 1);');
    // For this one, expand Display settings and uncheck boxes.
    $this->setUpScreenShot('structure-content-type-add-Display-Settings.png', 'onLoad="jQuery(\'#edit-display .details-wrapper).show(); jQuery(\'.vertical-tabs li:eq(0)).toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:eq(2)).toggleClass(\'is-selected\');" jQuery(\'#edit-display-submitted\').attr(\'checked\', 0);');
    // For this one, expand Menu settings and uncheck boxes.
    $this->setUpScreenShot('structure-content-type-add-Menu-Settings.png', 'onLoad="jQuery(\'#edit-menu .details-wrapper).show(); jQuery(\'.vertical-tabs li:eq(0)).toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:eq(3)).toggleClass(\'is-selected\');" jQuery(\'#edit-menu-options-main\').attr(\'checked\', 0);');

    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['vendor_type_name'],
        'description' => $this->demoInput['vendor_type_description'],
        'title_label' => $this->demoInput['vendor_type_title_label'],
        'options[promote]' => FALSE,
        'options[revision]' => TRUE,
        'display_submitted' => FALSE,
        'menu_options[main]' => FALSE,
      ], $this->callT('Save and manage fields'));
    $this->setUpScreenShot('structure-content-type-add-confirmation.png');

    // Final task for structure-content-type - Add content type for Recipe.
    // No screen shots.
    $this->drupalGet('admin/structure/types');
    $this->drupalGet('admin/structure/types/add');
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['recipe_type_name'],
        'description' => $this->demoInput['recipe_type_description'],
        'title_label' => $this->demoInput['recipe_type_title_label'],
        'options[promote]' => FALSE,
        'display_submitted' => FALSE,
        'menu_options[main]' => FALSE,
      ], $this->callT('Save and manage fields'));


    // Topic: structure-content-type-delete - Deleting a Content Type
    // Delete the Article content type.
    $this->drupalGet('admin/structure/types');
    $this->setUpScreenShot('structure-content-type-delete-dropdown.png', 'onLoad="jQuery(\'tr.odd .dropbutton-toggle button\').click();');

    $this->drupalGet('admin/structure/types/manage/article/delete');
    $this->setUpScreenShot('structure-content-type-delete-confirmation.png');

    $this->drupalPostForm(NULL, [], $this->callT('Delete'));
    $this->setUpScreenShot('structure-content-type-delete-confirm.png');

    // Topic: structure-fields - Adding basic fields to a content type.
    // Add Vendor URL field to Vendor content type.
    $this->drupalGet('admin/structure/types/manage/vendor/fields/add-field');
    // Fill in the form in the screenshot: choose Link for field type and
    // type in Vendor URL for the Label.
    $this->setUpScreenShot('structure-fields-add-field.png', 'onLoad="jQuery(\'#edit-new-storage-type\').val(\'link\'); jQuery(\'#new-storage-wrapper\').show(); jQuery(\'#edit-label\').val(\'' . $this->demoInput['vendor_field_url_label'] . '\');');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'link',
        'label' => $this->demoInput['vendor_field_url_label'],
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));
    $this->drupalPostForm(NULL, [
        'settings[link_type]' => 16,
        'settings[title]' => 0,
      ], $this->callT('Save settings'));
    // To make the screen shot, go back to the edit form for this field.
    $this->drupalGet('admin/structure/types/manage/vendor/fields/node.vendor.field_vendor_url');
    $this->setUpScreenShot('structure-fields-vendor-url.png');

    // Add Main Image field to Vendor content type.
    $this->drupalGet('admin/structure/types/manage/vendor/fields/add-field');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'image',
        'label' => $this->demoInput['vendor_field_image_label'],
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));
    $this->drupalPostForm(NULL, [
        'required' => 1,
        'settings[file_directory]' => $this->demoInput['vendor_field_image_directory'],
        'settings[min_resolution][x]' => 800,
        'settings[min_resolution][y]' => 600,
        'settings[max_filesize]' => '5 MB',
      ], $this->callT('Save settings'));
    $this->setUpScreenShot('structure-fields-result.png');
    // To make the screen shot, go back to the edit form for this field.
    $this->drupalGet('admin/structure/types/manage/vendor/fields/node.vendor.field_main_image');
    $this->setUpScreenShot('structure-fields-main-img.png');
    // Add the main image field to Recipe. No screenshots.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/add-field');
    $this->drupalPostForm(NULL, [
        'existing_storage_name' => 'field_main_image',
        'label' => $this->demoInput['recipe_field_image_label'],
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [
        'required' => 1,
        'settings[file_directory]' => $this->demoInput['recipe_field_image_directory'],
        'settings[min_resolution][x]' => 800,
        'settings[min_resolution][y]' => 600,
        'settings[max_filesize]' => '5 MB',
      ], $this->callT('Save settings'));

    // Create two Vendor content items. No screenshots.
    $this->drupalGet('node/add/vendor');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['vendor_1_title'],
        'files[field_main_image_0]' => $assets_directory . 'farm.jpg',
        'body[0][summary]' => $this->demoInput['vendor_1_summary'],
        'body[0][value]' => $this->demoInput['vendor_1_body'],
        'path[0][alias]' => $this->demoInput['vendor_1_path'],
        'field_vendor_url[0][uri]' => $this->demoInput['vendor_1_url'],
      ], $this->callT('Save and publish'));
    $this->drupalGet('node/add/vendor');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['vendor_2_title'],
        'files[field_main_image_0]' => $assets_directory . 'honey_bee.jpg',
        'body[0][summary]' => $this->demoInput['vendor_2_summary'],
        'body[0][value]' => $this->demoInput['vendor_2_body'],
        'path[0][alias]' => $this->demoInput['vendor_2_path'],
        'field_vendor_url[0][uri]' => $this->demoInput['vendor_2_url'],
      ], $this->callT('Save and publish'));

    // The next topic with screenshots is structure-taxonomy, but the
    // screenshot is generated later.

    // Topic: structure-taxonomy-setup - Setting Up a Taxonomy.
    $this->drupalGet('admin/structure/taxonomy');
    $this->setUpScreenShot('structure-taxonomy-setup-taxonomy-page.png');
    // Add Ingredients taxonomy vocabulary.
    $this->drupalGet('admin/structure/taxonomy/add');
    // Fill in the form in the screenshot, with the vocabulary name.
    $this->setUpScreenShot('structure-taxonomy-setup-add-vocabulary.png', 'onLoad="jQuery(\'#edit-name\').val(\'' . $this->demoInput['recipe-field-ingredients-label'] . '\');');
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['recipe-field-ingredients-label'],
      ], $this->callT('Save'));
    $this->setUpScreenShot('structure-taxonomy-setup-vocabulary-overview.png');
    // Add 3 sample terms.
    $this->drupalGet('admin/structure/taxonomy/manage/ingredients/add');
    // Fill in the form in the screenshot, with the term name Butter.
    $this->setUpScreenShot('structure-taxonomy-setup-add-term.png', 'onLoad="jQuery(\'#edit-name-0-value\').val(\'' . $this->demoInput['recipe-field-ingredients-term_1'] . '\');');
    // Add the rest of the terms, with no screenshots.
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe-field-ingredients-term_1'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe-field-ingredients-term_2'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe-field-ingredients-term_3'],
      ], $this->callT('Save'));

    // Add the Ingredients field to Recipe content type.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/add-field');
    // Fill in the form in the screenshot: choose Taxonomy term for field type
    // and type in Ingredients for the Label.
    $this->setUpScreenShot('structure-taxonomy-setup-add-field.png', 'onLoad="jQuery(\'#edit-new-storage-type\').val(\'field_ui:entity_reference:taxonomy_term\'); jQuery(\'#new-storage-wrapper\').show(); jQuery(\'#edit-label\').val(\'' . $this->demoInput['recipe_field_ingredients_label'] . '\');');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'field_ui:entity_reference:taxonomy_term',
        'label' => $this->demoInput['recipe_field_ingredients_label'],
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [
        'cardinality' => '-1',
      ], $this->callT('Save field settings'));
    $this->drupalPostForm(NULL, [
        'description' => $this->demoInput['recipe_field_ingredients_help'],
        'settings[handler_settings][target_bundles][ingredients]' => 1,
        'settings[handler_settings][auto_create]' => 1,
      ], $this->callT('Save settings'));
    $this->setUpScreenShot('structure-taxonomy-setup-field-settings-finished.png');
    // Go back and edit the field settings to make the next screenshot,
    // scrolling to the bottom.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/node.recipe.field_ingredients');
    $this->setUpScreenShot('structure-taxonomy-setup-field-settings-2.png', 'onLoad="window.scroll(0,2000);"');
    // And make the other screenshot from the edit settings page.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/node.recipe.field_ingredients/storage');
    $this->setUpScreenShot('structure-taxonomy-setup-field-settings.png');

    // Topic: structure-adding-reference - Adding a reference field.
    // Add the Submitted By field to Recipe content type.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/add-field');
    // Fill in the form in the screenshot: choose content reference for
    // field type and type in Submitted By for the Label.
    $this->setUpScreenShot('structure-adding-reference-add-field.png', 'onLoad="jQuery(\'#edit-new-storage-type\').val(\'field_ui:entity_reference:node\'); jQuery(\'#new-storage-wrapper\').show(); jQuery(\'#edit-label\').val(\'' . $this->demoInput['recipe_field_submitted_label'] . '\');');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'field_ui:entity_reference:node',
        'label' => $this->demoInput['recipe_field_submitted_label'],
      ], $this->callT('Save and continue'));
    $this->setUpScreenshot('structure-adding-reference-set-field-basic.png');
    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));
    $this->drupalPostForm(NULL, [
        'description' => $this->demoInput['recipe_field_submitted_help'],
        'required' => 1,
        'settings[handler_settings][target_bundles][vendor]' => 1,
        'settings[handler_settings][sort][field]' => 'title',
      ], $this->callT('Save settings'));
    $this->setUpScreenShot('structure-adding-reference-set-field-detailed.png');
    // Go back and edit the field settings to make the next screenshot,
    // scrolling to the bottom.
    $this->drupalGet('admin/structure/types/manage/recipe/fields/node.recipe.field_submitted_by');
    $this->setUpScreenShot('structure-adding-reference-content-type.png', 'onLoad="window.scroll(0,2000);"');


    // @todo Ready to add more topics here!


    // Topic (out of order): structure-taxonomy - Concept: Taxonomy.
    // Screen shot of Carrots taxonomy page after adding Recipe content items.
    // @todo make this screenshot.

    // Topic: language-add - Adding a language.
    // For non-English versions, locale and language will already be enabled.
    // For English, not yet. In both cases, we need config/content translation
    // though.
    $this->drupalGet('admin/modules');
    $values = [
      'modules[Multilingual][config_translation][enable]' => TRUE,
      'modules[Multilingual][content_translation][enable]' => TRUE,
    ];
    if ($this->demoInput['first_langcode'] == 'en') {
      $values += [
        'modules[Multilingual][language][enable]' => TRUE,
        'modules[Multilingual][locale][enable]' => TRUE,
      ];
    }
    $this->drupalPostForm(NULL, $values, $this->callT('Install'));

    $this->drupalGet('admin/config/regional/language');
    $this->drupalGet('admin/config/regional/language/add');
    $this->drupalPostForm(NULL, [
        'predefined_langcode' => $this->demoInput['second_langcode'],
      ], $this->callT('Add language'));
    $this->setUpScreenShot('language-add-list.png');

    // Topic: language-content-config - Configuring Content Translation
    $this->drupalGet('/admin/config/regional/content-language');
    // Simple screenshot of top section.
    $this->setUpScreenShot('language-content-config_custom.png');
    // For this screenshot, we need to check Content, and then under
    // Article and Basic Page, click the Show language selector button. Also
    // under Basic page, simulate expanding the drop-down for default language
    // by setting the 'size' attribute.
    $this->setUpScreenShot('language-content-config_content.png', 'onLoad="jQuery(\'#edit-entity-types-node\').attr(\'checked\', 1); jQuery(\'#edit-entity-types-block-content\').attr(\'checked\', 1); jQuery(\'#edit-entity-types-menu-link-content\').attr(\'checked\', 1); jQuery(\'#edit-settings-node\').show(); jQuery(\'#edit-settings-node-article-settings-language-language-alterable\').attr(\'checked\', 1); jQuery(\'#edit-settings-node-page-settings-language-langcode\').attr(\'size\', 7);"');

    $this->drupalPostForm(NULL, [
        'entity_types[node]' => TRUE,
        'entity_types[block_content]' => TRUE,
        'entity_types[menu_link_content]' => TRUE,

        'settings[node][page][translatable]' => TRUE,
        'settings[node][page][fields][title]' => TRUE,
        'settings[node][page][fields][uid]' => FALSE,
        'settings[node][page][fields][status]' => FALSE,
        'settings[node][page][fields][created]' => FALSE,
        'settings[node][page][fields][changed]' => FALSE,
        'settings[node][page][fields][promote]' => FALSE,
        'settings[node][page][fields][uid]' => FALSE,
        'settings[node][page][fields][sticky]' => FALSE,
        'settings[node][page][fields][path]' => TRUE,
        'settings[node][page][fields][body]' => TRUE,

        'settings[block_content][basic][translatable]' => TRUE,
        'settings[block_content][basic][fields][info]' => TRUE,
        'settings[block_content][basic][fields][changed]' => FALSE,
        'settings[block_content][basic][fields][body]' => TRUE,

        'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][title]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][description]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][changed]' => FALSE,
      ], $this->callT('Save configuration'));
    // Screenshot of the Basic page area after saving the configuration.
    $this->setUpScreenShot('language-content-config_custom.png');

  }

  /**
   * Clears the Drupal cache.
   *
   * @todo Remove this if it is not used.
   */
  protected function clearCache() {
    $this->drupalPostForm('admin/config/development/performance', [], $this->callT('Clear all caches'));
  }

  /**
   * Calls t() in the user interface, with the site's first language.
   *
   * For some unknown reason, when running this in non-English languages, the
   * form submits etc. are not working because it is not looking for the
   * correct (translated) button text when you make a call like
   * @code
   * $this->drupalPostForm('url/here', [], t('Button name'));
   * @endcode
   * So this method wraps t() by passing in the language code to translate
   * to, which is easier than trying to figure out what the real problem is.
   *
   * @param string $text
   *   Text to pass into t(). Must have been defined by another module or it
   *   will not be in the translation database.
   * @param bool $first
   *   (optional) TRUE (default) to translate to the first language in the
   *   demoInput member variable; FALSE to use the second language.
   *
   * @return string
   *   Original string, translated string, or a wrapper object that can be used
   *   like a string.
   */
  protected function callT($text, $first = TRUE) {
    if ($first) {
      $langcode = $this->demoInput['first_langcode'];
    }
    else {
      $langcode = $this->demoInput['second_langcode'];
    }

    if ($langcode == 'en') {
      return $text;
    }

    return t($text, [], ['langcode' => $langcode]);
  }

  /**
   * Makes clean screenshot output, and adds a note afterwards.
   *
   * The screen shot is of the current page.
   *
   * @param string $file
   *   Name of the screen shot file.
   * @param string $body_addition
   *   Additional text to add into the HTML body tag. Example:
   *   'onLoad="window.scroll(0,500);"'. This code should blank out irrelevant
   *   portions of the page, so that the ImageMagick capture script can trim
   *   the image automatically down to the right size.
   *
   * @see UserGuideDemoTestBase::showOnly()
   * @see UserGuideDemoTestBase::hideArea()
   * @see UserGuideDemoTestBase::setWidth()
   * @see UserGuideDemoTestBase::setBodyColor()
   * @see UserGuideDemoTestBase::removeScrollbars()
   * @see UserGuideDemoTestBase::addBorder()
   */
  protected function setUpScreenShot($file, $body_addition = '') {
    $output = str_replace('<body ', '<body ' . $body_addition . ' ', $this->getRawContent());

    // This is like TestBase::verbose() but just the bare HTML output, and
    // with a separate file counter so it doesn't interfere.
    $screenshot_filename =  $this->verboseClassName . '-screenshot-' . $this->screenshotId . '-' . $this->testId . '.html';
    if (file_put_contents($this->verboseDirectory . '/' . $screenshot_filename, $output)) {
      $url = $this->verboseDirectoryUrl . '/' . $screenshot_filename;
      $link = '<a href="' . $url . '" target="_blank">Screen shot output</a>';
      $this->error($link, 'User notice');
    }
    $this->screenshotId++;
    $this->pass('SCREENSHOT: ' . $file . ' ' . $url);
  }

  /**
   * Creates jQuery code to show only the selected part of the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to be shown. Single
   *   quotes must be escaped.
   * @param bool $border
   *   (optional) If TRUE, also add a white border around $selector. This is
   *   needed as a buffer for trimming the image, if the part you are trimming
   *   to is along the edge of the page. Defaults to FALSE.
   *
   * @return string
   *   jQuery code that will hide everything else on the page. Also puts a
   *   white border around the page for trimming purposes. Note that everything
   *   inside $selector is also shown, which may not be what you want.
   *
   * @see UserGuideDemoTestBase::hideArea()
   */
  protected function showOnly($selector, $border = FALSE) {
    // Hide everything.
    $code = "jQuery('*').hide(); ";
    // Show the selected item and its children and parents.
    $code .= "jQuery('" . $selector . "').show(); ";
    $code .= "jQuery('" . $selector . "').parents().show(); ";
    $code .= "jQuery('" . $selector . "').find('*').show(); ";
    // Add border and remove box shadow, if indicated.
    if ($border) {
      $code .= $this->addBorder($selector, '#ffffff', TRUE);
    }
    return $code;
  }

  /**
   * Creates jQuery code to hide the selected part of the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to hide. Single
   *   quotes must be escaped.
   *
   * @return string
   *   jQuery code that will hide this section of the page.
   *
   * @see UserGuideDemoTestBase::showOnly()
   */
  protected function hideArea($selector) {
    return "jQuery('" . $selector . "').hide(); ";
  }

  /**
   * Creates jQuery code to set the width of an area on the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to set the width of.
   *   Single quotes must be escaped.
   * @param int $width
   *   (optional) Number of pixels. Defaults to 600.
   *
   * @return string
   *   jQuery code that will set the width of this area.
   */
  protected function setWidth($selector, $width = 600) {
    return "jQuery('" . $selector . "').css('width', '" . $width . "px'); ";
  }

  /**
   * Creates jQuery code to set the body background color.
   *
   * This is useful to aid in being able to trim the screenshot automatically.
   * On some pages, non-white body background color may interfere with being
   * able to trim the page effectively.
   *
   * @param string $color
   *   (optional) Color to set. Defaults to white.
   *
   * @return string
   *   jQuery code that will set the background color.
   */
  protected function setBodyColor($color = '#ffffff') {
    return "jQuery('body').css('background', '" . $color . "'); ";
  }

  /**
   * Creates jQuery code to omit scrollbars.
   *
   * This is useful to aid in being able to trim the screenshot automatically.
   * On some pages, the scrollbars may interfere with the process.
   *
   * @return string
   *   jQuery code that will set the body to not overflow.
   */
  protected function removeScrollbars() {
    return "jQuery('body').css('overflow', 'hidden');";
  }

  /**
   * Creates jQuery code to put a 2px border around an area of a page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to add a border to.
   *   Single quotes must be escaped.
   * @param string $color
   *   A hex color code starting with #. Defaults to the standard red color.
   * @param bool $remove_shadow
   *   (optional) TRUE to also remove the box shadow. Defaults to FALSE.
   *
   * @return string
   *   jQuery code that adds the border.
   */
  protected function addBorder($selector, $color = '#e62600', $remove_shadow = FALSE) {
    $code = "jQuery('" . $selector . "').css('border', '2px solid " . $color . "'); ";
    if ($remove_shadow) {
      $code .= "jQuery('" . $selector . "').css('box-shadow', 'none'); ";
    }

    return $code;
  }

  /**
   * Prepares site settings and services before installation.
   *
   * Overrides WebTestBase::prepareSettings() so that we can store public
   * files in a directory that will not get removed until the verbose output
   * is gone.
   */
  protected function prepareSettings() {
    parent::prepareSettings();

    $this->publicFilesDirectory = $this->verboseDirectory . '/' . $this->databasePrefix;
    $settings['settings']['file_public_path'] = (object) [
      'value' => $this->publicFilesDirectory,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
  }

}
