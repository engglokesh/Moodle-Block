<?php
namespace block_moodletestblock\tests;

use advanced_testcase;
use block_moodletestblock;
use context_course;

/**
 * PHPUnit block_moodletestblock tests
 *
 * @package    block_moodletestblock
 * @category   test
 * @copyright  2022 Lokesh Malpani (engg.lokeshmalpani@gmail.com)
 */
class moodletestblock_test extends advanced_testcase {
    public static function setUpBeforeClass(): void {
        require_once(__DIR__ . '/../../moodleblock.class.php');
        require_once(__DIR__ . '/../block_moodletestblock.php');
    }

    /**
     * Test the behaviour of can_block_be_added() method.
     *
     * @covers ::can_block_be_added
     */
    public function test_can_block_be_added(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course and prepare the page where the block will be added.
        $course = $this->getDataGenerator()->create_course();
        $page = new \moodle_page();
        $page->set_context(context_course::instance($course->id));
        $page->set_pagelayout('course');

        $block = new block_moodletestblock();

        // If blogs advanced feature is enabled, the method should return true.
        set_config('enablecompletion', true);
        $this->assertTrue($block->can_block_be_added($page));

        // However, if the blogs advanced feature is disabled, the method should return false.
        set_config('enablecompletion', false);
        $this->assertFalse($block->can_block_be_added($page));
    }
}
