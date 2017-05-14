<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\SchoolYearNavigation;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_resultsByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('View Results by Course', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $timetableGateway = new TimetableGateway($pdo);
    $classResults = $timetableGateway->selectCourseResultsBySchoolYear($gibbonSchoolYearID, $sort);

    if (!$classResults || $classResults->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<h2>';
        echo __('Filter');
        echo '</h2>';

        $form = Form::create('resultsByCourse', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/Course Selection/tt_resultsByCourse.php');

        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray(array('nameShort' => __('Course Code'), 'name' => __('Course Name'), 'order' => __('Report Order'), 'students' => __('Students'),'issues' => __('Issues')))->selected($sort);

        $row = $form->addRow();
            $row->addSubmit('Go');

        echo $form->getOutput();

        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<div class="paginationTop">';
        echo __('Records').': '.$classResults->rowCount();
        echo '</div>';

        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('Course');
            echo '</th>';
            echo '<th>';
                echo __('Class');
            echo '</th>';
            echo '<th>';
                echo __('Balance');
            echo '</th>';
            echo '<th>';
                echo __('Students');
            echo '</th>';
            echo '<th>';
                echo __('Issues');
            echo '</th>';
            echo '<th style="weight: 60px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        $classEnrolmentMaximum = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMaximum');

        while ($class = $classResults->fetch()) {
            $rowClass = ($class['students'] < 8)? 'dull' : '';
            echo '<tr class="'.$rowClass.'">';
                echo '<td>'.$class['courseName'].'</td>';
                echo '<td>'.$class['className'].'</td>';
                echo '<td style="width:25%">';
                    if ($class['students'] > 0) {
                    $femaleBalance = (($class['studentsFemale'] / $classEnrolmentMaximum) * 100.0);
                    $maleBalance = (($class['studentsMale'] / $classEnrolmentMaximum) * 100.0);

                        echo '<div class="progressBar fill" style="width:100%">';
                            echo '<div class="complete" style="width:'.$femaleBalance.'%;" title="'.__('Female').' '.$class['studentsFemale'].'"></div>';
                            echo '<div class="highlight" style="width:'.$maleBalance.'%;" title="'.__('Male').' '.$class['studentsMale'].'"></div>';
                    }
                    echo '</div>';
                echo '</td>';
                echo '<td>'.$class['students'].'</td>';
                echo '<td>'.$class['issues'].'</td>';
                echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/tt_resultsByStudent.php&gibbonCourseID=".$class['gibbonCourseID']."&gibbonSchoolYearID=".$gibbonSchoolYearID."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
