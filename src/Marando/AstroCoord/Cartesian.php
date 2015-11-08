<?php

/*
 * Copyright (C) 2015 Ashley Marando
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Marando\AstroCoord;

use \Marando\IAU\IAU;
use \Marando\Units\Angle;
use \Marando\Units\Distance;
use \Marando\Units\Velocity;

/**
 * Represents a Cartesian XYZ position and velocity vector
 *
 * @property Distance $x     x position
 * @property Distance $y     y position
 * @property Distance $z     z position
 * @property Distance $r     Radial distance, r
 * @property Velocity $vx    x velocity
 * @property Velocity $vy    y velocity
 * @property Velocity $vz    z velocity
 * @property Velocity $vr    Radial velocity, r
 */
class Cartesian {

  use Traits\CopyTrait,
      \Marando\Units\Traits\SetUnitTrait,
      \Marando\Units\Traits\RoundingTrait;

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new Cartesian vector instance
   *
   * @param Distance $x     x position
   * @param Distance $y     y position
   * @param Distance $z     z position
   * @param Velocity $vx    x velocity
   * @param Velocity $vy    y velocity
   * @param Velocity $vz    z velocity
   */
  public function __construct(Distance $x, Distance $y, Distance $z,
          Velocity $vx = null, Velocity $vy = null, Velocity $vz = null) {

    // Set position components
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;

    // Set velocity components
    $this->vx = $vx;
    $this->vy = $vy;
    $this->vz = $vz;

    // Set default rounding and units
    $this->decimalPlaces = 13;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * x position
   * @var Distance
   */
  protected $x;

  /**
   * y position
   * @var Distance
   */
  protected $y;

  /**
   * z position
   * @var Distance
   */
  protected $z;

  /**
   * x velocity
   * @var Velocity
   */
  protected $vx;

  /**
   * y velocity
   * @var Velocity
   */
  protected $vy;

  /**
   * z velocity
   * @var Velocity
   */
  protected $vz;

  public function __get($name) {
    switch ($name) {
      case 'x':
      case 'y':
      case 'z':
      case 'vx':
      case 'vy':
      case 'vz':
        return $this->{$name};

      case "r":
        return $this->calcR();

      case "vr":
        return $this->calcVR();
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Adds another cartesian vector to this instance
   * @param static $b
   */
  public function add(Cartesian $b) {
    $this->x->add($b->x);
    $this->y->add($b->y);
    $this->z->add($b->z);
    if ($this->vx) {
      $this->vx->add($b->vx);
      $this->vy->add($b->vy);
      $this->vz->add($b->vz);
    }

    return $this;
  }

  /**
   * Subtracts another cartesian vector from this instance
   * @param static $b
   */
  public function subtract(Cartesian $b) {
    $this->x->subtract($b->x);
    $this->y->subtract($b->y);
    $this->z->subtract($b->z);
    if ($this->vx) {
      $this->vx->subtract($b->vx);
      $this->vy->subtract($b->vy);
      $this->vz->subtract($b->vz);
    }

    return $this;
  }

  /**
   * Converts this instance to an equatorial coordinate
   * @return Equat
   */
  public function toEquat() {
    // Cartesian to spherical
    IAU::C2s([$this->x->au, $this->y->au, $this->z->au], $theta, $phi);

    // Create RA and Declination components from radians
    $ra  = Angle::rad($theta)->norm()->toTime();
    $dec = Angle::rad($phi);

    // Return new equatorial instance using same frame and epoch
    return new Equat($ra, $dec, $this->r);
  }

  // // // Protected

  /**
   * Calculates the radial distance, r of this instance
   * @return Distance
   */
  protected function calcR() {
    $x = $this->x->au;
    $y = $this->y->au;
    $z = $this->z->au;

    return Distance::au(sqrt($x * $x + $y * $y + $z * $z));
  }

  /**
   * Calculates the radial velocity, vr of this instance
   * @return Velocity
   */
  protected function calcVR() {
    $x = $this->vx->aud;
    $y = $this->vy->aud;
    $z = $this->vz->aud;

    return Velocity::aud(sqrt($x * $x + $y * $y + $z * $z));
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    // Number format
    $format = "%+0.{$this->decimalPlaces}E";

    // Declare variables
    $x;
    $y;
    $z;
    $r;
    $vx;
    $vy;
    $vz;
    $vr;

    // Find units to use
    if ($this->unit == 'km km/d') {
      $du = 'km';
      $vu = 'km/d';

      $x = sprintf($format, $this->x->km);
      $y = sprintf($format, $this->y->km);
      $z = sprintf($format, $this->z->km);
      $r = sprintf($format, $this->r->km);
      if ($this->vx != null) {
        $vx = sprintf($format, $this->vx->kmd);
        $vy = sprintf($format, $this->vy->kmd);
        $vz = sprintf($format, $this->vz->kmd);
        $vr = sprintf($format, $this->vr->kmd);
      }
    }
    else if ($this->unit == 'km km/s') {
      $du = 'km';
      $vu = 'km/s';

      $x = sprintf($format, $this->x->km);
      $y = sprintf($format, $this->y->km);
      $z = sprintf($format, $this->z->km);
      $r = sprintf($format, $this->r->km);
      if ($this->vx != null) {
        $vx = sprintf($format, $this->vx->kms);
        $vy = sprintf($format, $this->vy->kms);
        $vz = sprintf($format, $this->vz->kms);
        $vr = sprintf($format, $this->vr->kms);
      }
    }
    else {
      $du = 'au';
      $vu = 'au/d';

      $x = sprintf($format, $this->x->au);
      $y = sprintf($format, $this->y->au);
      $z = sprintf($format, $this->z->au);
      $r = sprintf($format, $this->r->au);
      if ($this->vx != null) {
        $vx = sprintf($format, $this->vx->aud);
        $vy = sprintf($format, $this->vy->aud);
        $vz = sprintf($format, $this->vz->aud);
        $vr = sprintf($format, $this->vr->aud);
      }
    }

    // Form the string
    if ($this->vx != null) {
      return <<<STRING
 X $x $du
 Y $y $du
 Z $z $du
 R $r $du
VX $vx $vu
VY $vy $vu
VZ $vz $vu
VR $vr $vu
STRING;
    }
    else {
      return <<<STRING
 X $x $du
 Y $y $du
 Z $z $du
 R $r $du
STRING;
    }
  }

}
