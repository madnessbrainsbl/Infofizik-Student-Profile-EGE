<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     local_studentprofile
 * @category    upgrade
 * @copyright   2024 Vlad Pereskokov <it-vpereskokov@mail.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_studentprofile upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_studentprofile_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $v = 2024073102;
    if ($oldversion < $v) {

        // Define table ege_student_grade to be created.
        $table = new xmldb_table('ege_student_grade');

        // Adding fields to table ege_student_grade.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ege_student_grade.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table ege_student_grade.
        $table->add_index('changemeidx_user_courser', XMLDB_INDEX_UNIQUE, ['user_id', 'course_id']);

        // Conditionally launch create table for ege_student_grade.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // NOTE: updated in step two
        // upgrade_plugin_savepoint(true, $v, 'local', 'studentprofile');
    }

    $v = 2024073102;
    if ($oldversion < $v) {

        // Define table ege_point_map to be created.
        $table = new xmldb_table('ege_point_map');

        // Adding fields to table ege_point_map.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('from_point', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('to_point', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ege_point_map.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for ege_point_map.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Studentprofile savepoint reached.
        upgrade_plugin_savepoint(true, $v, 'local', 'studentprofile');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////
    // release: 0.2.0
    ////////////////////////////////////////////////////////////////////////////////////////////////////

    $v = 2024082503;
    if ($oldversion < $v) {

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // telegram student to chat table
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // Define table telegram_student_chat to be created.
        $table = new xmldb_table('telegram_student_chat');

        // Adding fields to table telegram_student_chat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('group_chat_id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '10', null, null, null, '1');
        $table->add_field('time_last_message', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table telegram_student_chat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for telegram_student_chat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // settings table
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        // Define table studentprofile_settings to be created.
        $table = new xmldb_table('studentprofile_settings');

        // Adding fields to table studentprofile_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('setting_key', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('json_value', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table studentprofile_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for studentprofile_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Studentprofile savepoint reached.
        upgrade_plugin_savepoint(true, $v, 'local', 'studentprofile');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////
    // release: 0.2.1
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    $v = 2024082810;
    if ($oldversion < $v) {

        // Define table ege_student_grade to be created.
        $table = new xmldb_table('weekly_variant_counts');

        // Adding fields to table ege_student_grade.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ege_student_grade.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table ege_student_grade.
        $table->add_index('changemeidx_user_courser', XMLDB_INDEX_UNIQUE, ['user_id', 'course_id']);

        // Conditionally launch create table for ege_student_grade.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Studentprofile savepoint reached.
        upgrade_plugin_savepoint(true, $v, 'local', 'studentprofile');
    }

    return true;
}
