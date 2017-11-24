<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Base view class for rendering html-template based output
 * (from a file.tpl.php file).
 *
 * Child classes should set the template_path property in their constructors, and
 * do not need any special renderView code.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_HTMLTemplate extends Views_Base {

    protected $base_template_path = ENTRADA_ABSOLUTE;
    protected $template_path = "";
    protected $template_error = "Unknown error";
    protected $replace_tokens = true;

    public function setTemplatePath($template_path) {
        $this->template_path = $template_path;
    }

    public function setBaseTemplatePath($template_path) {
        $this->base_template_path = $template_path;
    }

    /**
     * For template based views, override the default behaviour of generateOutput.
     *
     * @param array $options
     */
    protected function generateOutput($options = array()) {
        $error = true;
        if ($this->base_template_path.$this->template_path) {
            if (file_exists($this->base_template_path.$this->template_path)) {
                if ($this->validateOptions($options)) {
                    $error = false;
                } else {
                    $this->template_error = "options validation failed";
                }
            } else {
                $this->template_error = "template '{$this->template_path}' not found";
            }
        } else {
            $this->template_error = "no template file specified";
        }

        if ($error) {
            $this->renderError();
        } else {

            // Generate the HTML from the tpl file
            ob_start();
            $this->renderView($options);
            $output = ob_get_contents();
            ob_end_clean();

            if ($options) {
                $tokens = array();
                $matches = array();

                // Replace tokens with $options parameters
                // Template tokens are wrapped in % characters. This function finds all tokens within a stored output buffer.
                preg_match_all('/\%(.*?)\%/', $output, $matches);

                if (!empty($matches)) {
                    if (isset($matches[0]) && is_array($matches[0]) && !empty($matches[0])) {
                        $tokens = $matches[0];
                    }
                }

                // Find those tokens in the $options array
                foreach ($tokens as $i => $token) {
                    if (isset($options[$token]) && is_string($options[$token])) {
                        $output = str_replace($token, $options[$token], $output);
                    }
                }
            }

            // Echo the output (output is captured (or not) by Views_Base::render())
            echo $output;
        }
    }

    /**
     * Template-based renderView.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; // Needed for scope; the included template file may make use of it.
        extract($options);
        include($this->base_template_path.$this->template_path);
    }

    /**
     * Render template error.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo sprintf($translate->_("Unable to render template (error: %s)"), $this->template_error); ?></strong>
        </div>
        <?php
    }
}
