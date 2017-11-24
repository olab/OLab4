<?php
if (!isset($logo_image_data) || !is_array($logo_image_data) || !$logo_image_data) {
    $logo_image_data = array();
}
if (!isset($user_image_data) || !is_array($user_image_data) || !$user_image_data) {
    $user_image_data = array();
}
if (!is_null($form_title) || !is_null($header)) { ?>
    <table class="header-block">
        <tbody>
        <tr>
            <?php
            if (!empty($logo_image_data)) { ?>
                <td width="30%">
                    <img src="data:{<?php echo $logo_image_data["mime_type"]; ?>};base64,{<?php echo $logo_image_data["photo"]; ?>}" class="pdf-logo-image"/>
                </td>
                <?php
            }

            if (!is_null($header)) { ?>
                <td width="40%">
                    <?php if (!is_null($form_title)) { ?>
                        <h1 class="form-heading"><?php echo html_encode($form_title); ?></h1>
                        <?php
                    }
                    echo $header; ?>
                </td>
                <?php
            }

            if (!empty($user_image_data)) { ?>
                <td width="30%" class="pdf-user-image-container">
                    <img src="data:{<?php echo $user_image_data["mime_type"]; ?>};base64,{<?php echo $user_image_data["photo"]; ?>}" class="pdf-user-image"/>
                </td>
                <?php
            }
            ?>
        </tr>
        </tbody>
    </table>
    <?php
}
?>