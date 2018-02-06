<div class="container-fluid">
    <div class="row">
    <?php
        if ($hasfooterleft) {
            echo $OUTPUT->synergyblocks($footerl, 'col-md-4 col-sm-6 col-12');
        }
        if ($hasfootermiddle) {
            echo $OUTPUT->synergyblocks($footerm, 'col-md-4 col-sm-6 col-12');
        }
        if ($hasfooterright) {
            echo $OUTPUT->synergyblocks($footerr, 'col-md-4 col-sm-6 col-12');
        }
    ?>
    </div>

    <div class="footerlinks row">
        <?php
            // Footnote
            echo '<div class="footnote col-md-8"">';
            if ($hasfootnote) {
                echo $hasfootnote;
            }
            echo '</div>';

            // Copyright
            echo '<div class="logo col-md-4 text-center text-md-right">';
            echo html_writer::start_tag('a', array('href'=>'http://www.qmul.ac.uk/'));
            echo html_writer::empty_tag('img', array('src'=>$CFG->wwwroot.'/theme/qmul/pix/footer-logo.png'));
            echo html_writer::end_tag('a');
            echo "<div class='footnote mt-1 text-center text-md-right'>";
                if ($hascopyright) {
                    echo '&copy; '.date("Y").' '.$hascopyright;
                }
                if ($hasfootright) {
                    echo $hasfootright;
                }
            echo '</div>';
            echo '</div>';
        ?>
    </div>
    <div class="text-center">
        <?php if (isloggedin()) { ?>
            <a href="<?php echo $CFG->wwwroot ?>/login/logout.php" class="btn btn-secondary"><?php echo get_string('logout'); ?></a>
        <?php } ?>
    </div>
</div>