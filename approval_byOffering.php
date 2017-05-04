<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Modules\CourseSelection\SchoolYearNavigation;
use Gibbon\Modules\CourseSelection\Domain\ToolsGateway;
use Gibbon\Modules\CourseSelection\Domain\OfferingsGateway;
use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/approval_byOffering.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Course Approval by Offering', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = new ToolsGateway($pdo);
    $offeringsGateway = new OfferingsGateway($pdo);
    $selectionsGateway = new SelectionsGateway($pdo);

    $courseSelectionOfferingID = $_REQUEST['courseSelectionOfferingID'] ?? '';
    $showRemoved = $_GET['showRemoved'] ?? 'N';
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    // SELECT OFFERING
    $form = Form::create('courseApprovalByOffering', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/approval_byOffering.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('sidebar', 'false');

    $offeringsResults = $offeringsGateway->selectAllBySchoolYear($gibbonSchoolYearID);

    if ($offeringsResults->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
        return;
    }

    $offerings = $offeringsResults->fetchAll();
    $offeringsArray = array_combine(array_column($offerings, 'courseSelectionOfferingID'), array_column($offerings, 'name'));

    $row = $form->addRow();
        $row->addLabel('courseSelectionOfferingID', __('Offering'));
        $row->addSelect('courseSelectionOfferingID')->fromArray($offeringsArray)->isRequired()->placeholder()->selected($courseSelectionOfferingID);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    $offeringResult = $offeringsGateway->selectOne($courseSelectionOfferingID);
    $offering = ($offeringResult && $offeringResult->rowCount() > 0)? $offeringResult->fetch() : array();

    if (empty($offering)) {
        return;
    }

    // LIST STUDENTS
    if (!empty($courseSelectionOfferingID)) {
        $studentChoicesResults = $selectionsGateway->selectStudentsByOffering($courseSelectionOfferingID);

        if ($studentChoicesResults->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
        } else {

            echo '<br/><p>';
            echo sprintf(__('Showing %1$s student course selections:'), $studentChoicesResults->rowCount());
            echo '</p>';

            echo '<table class="fullWidth colorOddEven" cellspacing="0">';

            while ($student = $studentChoicesResults->fetch()) {

                $choicesResults = $selectionsGateway->selectChoicesByOfferingAndPerson($courseSelectionOfferingID, $student['gibbonPersonID']);
                $choices = ($choicesResults && $choicesResults->rowCount() > 0)? $choicesResults->fetchAll() : array();

                $status = __('In Progress');
                $rowClass = '';

                if (count($choices) >= $offering['minSelect']) {
                    $status = __('Complete');
                    //$rowClass = 'current';
                }

                echo '<tr class="'.$rowClass.'" id="'.$student['gibbonPersonID'].'">';
                    echo '<td width="15%">';
                        echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'" target="_blank">';
                        echo getUserPhoto($guid, $student['image_240'], 75).'<br/>';
                        echo formatName('', $student['preferredName'], $student['surname'], 'Student', true);
                        echo '</a><br/>';
                        echo $student['rollGroupName'];
                    echo '</td>';

                    echo '<td width="35%">';
                        if (count($choices) > 0) {
                            foreach ($choices as $choice) {
                                $checked = ($choice['approval'] == 'Approved')? 'checked' : '';

                                echo '<div class="courseChoiceContainer" data-status="'.$choice['approval'].'">';
                                echo '<input type="checkbox" name="'.$student['gibbonPersonID'].'" class="courseSelectionApproval" value="'.$choice['courseSelectionChoiceID'].'" data-student="'.$student['gibbonPersonID'].'" '.$checked.'/> &nbsp;';

                                echo $choice['courseName'];

                                if ($choice['status'] == 'Required') {
                                    echo '<span class="pullRight courseTag small emphasis">'.$choice['status'].'</span>';
                                }
                                echo '</div>';
                            }
                        }
                    echo '</td>';
                    echo '<td width="35%">';
                        echo '<strong>'.$status.'</strong>: ';

                        echo sprintf(__('%1$s of %2$s courses selected'), count($choices), $offering['minSelect']).'<br/>';

                    echo '</td>';
                    echo '<td width="15%">';

                        echo "<a onclick='courseSelectionApproveAll(\"".$student['gibbonPersonID']."\")' style='cursor:pointer;'><img title='".__('Approve All')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick_double.png'/></a> &nbsp;&nbsp;&nbsp;";

                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&gibbonPersonIDStudent=".$student['gibbonPersonID']."&sidebar=false&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."' target='_blank'><img title='".__('View Course Selections')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> &nbsp;&nbsp;";

                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/report_studentGrades.php&gibbonPersonIDStudent=".$student['gibbonPersonID']."&sidebar=false' target='_blank'><img title='".__('Student Grades')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/internalAssessment.png'/></a>";

                    echo '</td>';
                echo '</tr>';
            }

            echo '</table>';

            ?>
            <script>
                $('.courseSelectionApproval').change(function() {
                    courseSelectionApprovalSave($(this), <?php echo $courseSelectionOfferingID; ?>, '<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/'; ?>');
                });
            </script>
            <?php
        }
    }
}