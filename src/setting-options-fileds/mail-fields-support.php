

<h1>Mail Settings Options</h1>

<div class="wrap">
    <div class="cf7-dbt-container bulkmail">
        <div class="cf7-dbt-content">
            <?php settings_errors(); ?>
            <form method="post" action="options.php" class="mail-settings">
                <?php settings_fields( 'mail-provider-support-group' ); ?>
                <?php do_settings_sections( 'mail-support-options' ) ?>


                <?php submit_button(); ?>

            </form>
        </div>
        <div class="cf7-dbt-sidebar">
            <div class="cf7-dbt-sidebar-inner">
                <div class="cf7-dbt-rating">
                    <h4 style="margin: 0 0 10px">Rate Our Plugin</h4>
                    <p style="margin: 0 0 10px">If you like our plugin please give us a feedback. Any suggestion will be appreciated.</p>
                    <a href="https://wordpress.org/support/plugin/cf7-db-tool/reviews/" class="button button-primary" target="_blank">Rate us Now</a>
                    <a href="https://orangetoolz.com/cf7-db-tool-support/"  title="Contact for new feature" class="button button-primary" target="_blank">Support</a>
                </div>
            </div>
        </div>
    </div>
</div>



