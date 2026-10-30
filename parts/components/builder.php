    <?php
    if (have_rows('builder')) : ?>
        <div class="builder sp-8">
            <?php
            while (have_rows('builder')) : the_row();
                $blockname = get_row_layout();
                $filename = str_replace('_', '-', $blockname);
                // Define full path to the component file
                $file_path = THEME_DIR . 'parts/components/builder-blocks/' . $filename . '.php';

                if (file_exists($file_path)) {
                    include $file_path;
                }
            endwhile; ?>
        </div>
    <?php endif; ?>