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
 * Course Selection: gibbonSettings Table Gateway
 *
 * @version v14
 * @since   17th April 2017
 * @author  Sandra Kuipers
 */
class SettingsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function update($scope, $name, $value)
    {
        $data = array('scope' => $scope, 'name' => $name, 'value' => $value);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope=:scope AND name=:name";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }
}