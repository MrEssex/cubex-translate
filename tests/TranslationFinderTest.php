<?php

namespace MrEssex\CubexTranslate\Tests;

use Cubex\Context\Context;
use MrEssex\CubexTranslate\TranslationFinder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;

class TranslationFinderTest extends TestCase
{

  protected function setUp(): void
  {
    parent::setUp();
    $this->_cleanTestFolder();
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    $this->_cleanTestFolder();
  }

  public function testProcessFile(): void
  {
    $contents = '<?php echo $this->_("messageID", "Hello, World!");';
    $finder = TranslationFinder::withContext($this->_mockContext(), new ConsoleOutput());
    $finder->processFile($contents);

    // tpl file should be created
    $this->assertFileExists($this->_tplLocation());

    $tplContents = require $this->_tplLocation();
    $this->assertArrayHasKey('messageID', $tplContents);
    $this->assertEquals('Hello, World!', $tplContents['messageID']['_']);
    unset($tplContents);
  }

  public function testSimpleTranslation(): void
  {
    $contents = '<?php echo $this->_t("This is a simple Translation");';
    $finder = TranslationFinder::withContext($this->_mockContext(), new ConsoleOutput());
    $finder->processFile($contents);

    // tpl file should be created
    $this->assertFileExists($this->_tplLocation());

    $tplContents = require $this->_tplLocation();
    $this->assertArrayHasKey(md5('This is a simple Translation'), $tplContents);
    $this->assertEquals('This is a simple Translation', $tplContents[md5('This is a simple Translation')]['_']);
    unset($tplContents);
  }

  public function testPluralTranslation(): void
  {
    $contents = '<?php echo $this->_p("This is a simple Translation", "These are simple Translations", 2);';
    $finder = TranslationFinder::withContext($this->_mockContext(), new ConsoleOutput());
    $finder->processFile($contents);

    // tpl file should be created
    $this->assertFileExists($this->_tplLocation());

    $tplContents = require $this->_tplLocation
    ();
    $key = md5('This is a simple Translation' . 'These are simple Translations');
    $this->assertArrayHasKey($key, $tplContents);
    $this->assertEquals('This is a simple Translation', $tplContents[$key]['1']);
    $this->assertEquals('These are simple Translations', $tplContents[$key]['n..0,2..n']);
    unset($tplContents);
  }

  public function testSimplePluralTranslation(): void
  {
    $contents = '<?php echo $this->_sp("x step(s)", 2);';
    $finder = TranslationFinder::withContext($this->_mockContext(), new ConsoleOutput());
    $finder->processFile($contents);

    // tpl file should be created
    $this->assertFileExists($this->_tplLocation());

    $tplContents = require $this->_tplLocation();
    $key = md5('x step' . 'x steps');
    $this->assertArrayHasKey($key, $tplContents);
    $this->assertEquals('x step', $tplContents[$key]['1']);
    $this->assertEquals('x steps', $tplContents[$key]['n..0,2..n']);
    unset($tplContents);
  }

  protected function _mockContext(): Context
  {
    $context = new Context();
    $context->setProjectRoot(__DIR__);

    return $context;
  }

  protected function _cleanTestFolder(): void
  {
    $context = $this->_mockContext();

    // remove translations directory
    $translationsDir = $context->getProjectRoot() . '/translations';
    if(file_exists($translationsDir))
    {
      $this->_removeDirectory($translationsDir);
    }
  }

  protected function _removeDirectory($dir): bool
  {
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach($files as $file)
    {
      (is_dir("$dir/$file")) ? $this->_removeDirectory("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  }

  protected function _tplLocation(): string
  {
    return __DIR__ . '/translations/_tpl.php';
  }
}
