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
 * A class to produce PDFs for assessments.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Assessments_HTMLForPDFGenerator extends Entrada_Assessments_Base {
    private $pdf = null;
    private $configured = false;

    public function getHtmlTypeConst() {
        return \mikehaertl\wkhtmlto\Pdf::TYPE_HTML;
    }

    /**
     * Debug dump the object.
     */
    public function debug() {
        Zend_Debug::dump($this->pdf);
    }

    /**
     * Create an instance of the HTML to PDF object, ensuring that the underlying required functionality (wkhtmltopdf) exists and is accessible.
     *
     * @return bool
     */
    public function configure() {
        global $APPLICATION_PATH;

        if ($this->configured) {
            return true;
        }
        if (isset($APPLICATION_PATH["wkhtmltopdf"]) && $this->commandExists($APPLICATION_PATH["wkhtmltopdf"])) {
            $this->pdf = new \mikehaertl\wkhtmlto\Pdf();
            $this->pdf->binary = $APPLICATION_PATH["wkhtmltopdf"];
            $this->pdf->ignoreWarnings = true;
            $this->configured = true;
            return true;
        }
        return false;
    }

    /**
     * Reset the PDF generator object.
     */
    public function reset() {
        $this->pdf = new \mikehaertl\wkhtmlto\Pdf();
    }

    /**
     * Send this HTML as PDF to the browser.
     *
     * NOTE: The $html variable must contain <html> and <body> tags, otherwise the PDF will not be generated and throw an error.
     *
     * @param string $filename
     * @param string $html
     * @param bool $add_as_page
     * @return bool
     */
    public function send($filename, $html = null, $add_as_page = true) {
        $status = false;
        if ($this->configured && $this->pdf) {
            if ($add_as_page && $html) {
                $this->pdf->addPage($html);
            }
            $status = $this->pdf->send($filename);
            if (!$status) {
                application_log("error", "Error creating PDF for filename '$filename'");
            }
        }
        return $status;
    }

    /**
     * Add HTML via the PDF library's add page method.
     *
     * @param $html
     * @param array $options
     * @param null $type
     */
    public function addHTMLPage($html, $options = array(), $type = null) {
        if ($this->pdf && $html) {
            $this->pdf->addPage($html, $options, $type);
        }
    }

    /**
     * Output the PDF as string.
     *
     * @param string $html
     * @return mixed
     */
    public function toString($html=null) {
        if ($this->configured && $this->pdf) {
            if ($html) {
                $this->pdf->addPage($html);
            }
            return $this->pdf->toString();
        }
        return false;
    }

    /**
     * Generate the HTML used in the assessment form PDF.
     *
     * @param $assessment_html
     * @param $organisation_id
     * @param $form_title
     * @param $header
     * @param $user_id
     * @param $description
     * @return string
     */
    public function generateAssessmentHTML($assessment_html, $organisation_id, $form_title = null, $header = null, $user_id = false, $description = null) {
        ob_clear_open_buffers();
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/rubrics.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/items.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessments.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-form.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-pdf.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/templates/default/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
        </head>
        <body>
            <div class="blank-space"></div>
            <?php
            $cache = new Entrada_Utilities_Cache();
            $logo_image_data = $cache->loadCache("organisation_logo_$organisation_id");
            $user_image_data = array();
            if ($user_id) {
                $user_image_data = $cache->loadCache($user_id);
            }
            $template_view = new Views_HTMLTemplate();
            $organisation = Models_Organisation::fetchRowByID($organisation_id);
            $full_path = "/templates/{$organisation->getTemplate()}/views/assessments/header.tpl.php";
            $template_view->setTemplatePath($full_path);
            $template_view->render(
                array(
                    "form_title"      => $form_title,
                    "header"          => $header,
                    "logo_image_data" => $logo_image_data,
                    "user_image_data" => $user_image_data
                )
            );
            if (!is_null($description)): ?>
                <div class="assessment-report-node">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td class="form-search-message text-center" colspan="4">
                                    <p class="no-search-targets space-above space-below medium"><?php echo $description; ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; 
            echo $assessment_html;
            ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate the HTML used in the Enrollment PDF.
     *
     * @param $enrollment
     * @param $form_title
     * @param $header
     * @return string
     */
    public function generateEnrollmentHTML($enrollment, $form_title, $header) {
        ob_clear_open_buffers();
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-public-index.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/templates/default/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
            <style type="text/css">
                ul, li {
                    page-break-inside: avoid !important;
                }
            </style>
        </head>
        <body>
            <?php echo $header ?>
            <h3 id="form-heading"><?php echo html_encode($form_title); ?></h3>
            <?php echo $enrollment; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

	/**
	 * Generate the HTML used in the assessment report PDF.
	 *
	 * @param $report_html
	 * @return string
	 */
    public function generateAssessmentReportHTML($report_html) {
		ob_clear_open_buffers();
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-public-index.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/templates/default/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<style type="text/css">
				thead, tfoot {
					display: table-row-group;
				}

				p.comment {
					text-align: left;
				}

				table, tr, td, th, tbody, thead, tfoot {
					page-break-inside: avoid !important;
				}
			</style>
		</head>
		<body>
		<?php echo $report_html; ?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate the HTML used in the assessment report PDF.
	 *
	 * @param $form_html
	 * @param $form
	 * @return string
	 */
    public function generateAssessmentFormHTML($form_html, $form) {
		global $translate;

		ob_clear_open_buffers();
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/rubrics.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/items.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessments.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-form.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/templates/default/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/>
			<style type="text/css">
				thead, tfoot {
					display: table-row-group;
				}

				p.comment {
					text-align: left;
				}

				table.free-text tbody tr.response-label {
					height: 300px;
				}

				.element_label {
					font-size: 18px;
					font-weight: normal;
				}

				table, tr, td, th, tbody, thead, tfoot {
					page-break-inside: avoid !important;
				}
			</style>
		</head>
		<body>
		<h1 id="form-heading"><span class="element_label"><?php echo $translate->_("Form Title")?>:</span> <?php echo html_encode($form->getTitle()); ?></h1>
		<h2 id="form-heading"><span class="element_label"><?php echo $translate->_("Form Description")?>:</span> <?php echo html_encode($form->getDescription()); ?></h2>
		<?php
            echo $form_html;
        ?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}