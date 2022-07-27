<?php
/**
 * Completion status block caps.
 *
 * @package    block_moodletestblock
 * @copyright  Lokesh Malpani <engg.lokeshmalpani@gmail.com>

 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/moodletestblock:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);
