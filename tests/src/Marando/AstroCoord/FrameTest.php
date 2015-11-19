<?php

namespace Marando\AstroCoord;

use \Marando\AstroDate\AstroDate;
use \Marando\AstroDate\Epoch;
use \PHPUnit_Framework_TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-18 at 15:18:10.
 */
class FrameTest extends PHPUnit_Framework_TestCase {

  /**
   * @covers Marando\AstroCoord\Frame::ICRF
   */
  public function testICRF() {
    $ircf = Frame::ICRF();
    $this->assertEquals('ICRF', $ircf->name);
    $this->assertEquals(Epoch::J2000(), $ircf->equinox);
  }

  /**
   * @covers Marando\AstroCoord\Frame::FK5
   */
  public function testFK5() {
    $equinoxes = [
        Epoch::J2000(),
        Epoch::B1950(),
        Epoch::J(2015),
        Epoch::dt(AstroDate::now()),
    ];

    foreach ($equinoxes as $e) {
      $equinox = $e;
      $fk5     = Frame::FK5($e);

      $this->assertEquals('FK5', $fk5->name);
      $this->assertEquals($equinox, $fk5->equinox);
    }
  }

  /**
   * @covers Marando\AstroCoord\Frame::FK4
   */
  public function testFK4() {
    $equinoxes = [
        Epoch::B1950(),
        Epoch::B1900(),
        Epoch::B(1948),
        Epoch::dt(AstroDate::now()),
    ];

    foreach ($equinoxes as $e) {
      $equinox = $e;
      $fk4     = Frame::FK4($e);

      $this->assertEquals('FK4', $fk4->name);
      $this->assertEquals($equinox, $fk4->equinox);
    }
  }

}