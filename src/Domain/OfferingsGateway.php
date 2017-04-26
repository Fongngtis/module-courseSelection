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

namespace Gibbon\Modules\CourseSelection\Domain;

/**
 * Course Selection: courseSelectionOffering Table Gateway
 *
 * @version v14
 * @since   13th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionOffering
 * @uses  courseSelectionOfferingRestriction
 * @uses  courseSelectionOfferingBlock
 * @uses  courseSelectionBlock
 * @uses  courseSelectionBlockCourse
 * @uses  gibbonStudentEnrolment
 * @uses  gibbonSchoolYear
 * @uses  gibbonYearGroup
 * @uses  gibbonDepartment
 */
class OfferingsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    // OFFERINGS

    public function selectAllBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as schoolYearName, GROUP_CONCAT(CONCAT(restrictYear.name, ' - ', gibbonYearGroup.nameShort) ORDER BY restrictYear.sequenceNumber, gibbonYearGroup.sequenceNumber SEPARATOR '<br/>') as yearGroupNames
                FROM courseSelectionOffering
                JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN courseSelectionOfferingRestriction ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN gibbonSchoolYear AS restrictYear ON (restrictYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY courseSelectionOfferingID
                ORDER BY sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectOne($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as schoolYearName FROM courseSelectionOffering JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE courseSelectionOfferingID=:courseSelectionOfferingID ";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectOfferingsByStudentEnrolment($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT *
                FROM courseSelectionOffering
                LEFT JOIN courseSelectionOfferingRestriction ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN gibbonStudentEnrolment ON (
                    gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID
                    AND gibbonStudentEnrolment.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY courseSelectionOffering.courseSelectionOfferingID
                HAVING (COUNT(courseSelectionOfferingRestrictionID) = 0 OR COUNT(gibbonStudentEnrolmentID) > 0)
                ORDER BY courseSelectionOffering.sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function delete($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOffering WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function getNextSequenceNumber()
    {
        $sql = "SELECT MAX(sequenceNumber) FROM courseSelectionOffering";
        $result = $this->pdo->executeQuery(array(), $sql);

        return ($result && $result->rowCount() > 0)? $result->fetchColumn(0)+1 : 1;
    }

    // OFFERING RESTRICTIONS

    public function selectAllRestrictionsByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionOfferingRestriction.*, gibbonSchoolYear.name as schoolYearName, gibbonYearGroup.name as yearGroupName
                FROM courseSelectionOfferingRestriction
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOfferingID=:courseSelectionOfferingID ";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertRestriction(array $data)
    {
        $sql = "INSERT INTO courseSelectionOfferingRestriction SET courseSelectionOfferingID=:courseSelectionOfferingID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function deleteRestriction($courseSelectionOfferingRestrictionID)
    {
        $data = array('courseSelectionOfferingRestrictionID' => $courseSelectionOfferingRestrictionID);
        $sql = "DELETE FROM courseSelectionOfferingRestriction WHERE courseSelectionOfferingRestrictionID=:courseSelectionOfferingRestrictionID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function deleteAllRestrictionsByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOfferingRestriction WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    // OFFERING BLOCKS

    public function selectAllBlocksByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionBlock.gibbonDepartmentIDList, courseSelectionOfferingBlock.*, courseSelectionBlock.name as blockName, courseSelectionBlock.description as blockDescription, courseSelectionOfferingBlock.minSelect, courseSelectionOfferingBlock.maxSelect, COUNT(gibbonCourseID) as courseCount
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionBlock ON (courseSelectionBlock.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                LEFT JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID)
                WHERE courseSelectionOfferingID=:courseSelectionOfferingID
                GROUP BY courseSelectionBlock.courseSelectionBlockID
                ORDER BY courseSelectionOfferingBlock.sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertBlock(array $data)
    {
        $sql = "INSERT INTO courseSelectionOfferingBlock SET courseSelectionOfferingID=:courseSelectionOfferingID, courseSelectionBlockID=:courseSelectionBlockID, minSelect=:minSelect, maxSelect=:maxSelect";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function updateBlockOrder(array $data)
    {
        $sql = "UPDATE courseSelectionOfferingBlock SET sequenceNumber=:sequenceNumber WHERE courseSelectionOfferingID=:courseSelectionOfferingID AND courseSelectionBlockID=:courseSelectionBlockID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function deleteBlock($courseSelectionOfferingID, $courseSelectionBlockID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "DELETE FROM courseSelectionOfferingBlock WHERE courseSelectionOfferingID=:courseSelectionOfferingID AND courseSelectionBlockID=:courseSelectionBlockID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function deleteAllBlocksByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOfferingBlock WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    // FORM QUERIES

    public function selectAvailableBlocksBySchoolYear($courseSelectionOfferingID, $gibbonSchoolYearID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionBlock.courseSelectionBlockID as value, CONCAT(courseSelectionBlock.name, ' (', COUNT(courseSelectionBlockCourse.gibbonCourseID), ' courses)') as name
                FROM courseSelectionBlock
                JOIN gibbonSchoolYear ON (courseSelectionBlock.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID AND courseSelectionOfferingID=:courseSelectionOfferingID)
                LEFT JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID)
                WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                AND courseSelectionOfferingBlock.courseSelectionBlockID IS NULL
                GROUP BY courseSelectionBlock.courseSelectionBlockID
                ORDER BY name";

        return $this->pdo->executeQuery($data, $sql);
    }

    // MISC

    public function selectDepartmentByID($gibbonDepartmentID)
    {
        $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
        $sql = "SELECT* FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }
}
