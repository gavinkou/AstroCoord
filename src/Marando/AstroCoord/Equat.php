<?php

/*
 * Copyright (C) 2015 ashley
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

use \Marando\AstroCoord\Horiz;
use \Marando\Units\Pressure;
use \Marando\Units\Temperature;
use \Marando\AstroDate\Epoch;
use \Marando\AstroDate\AstroDate;
use \Marando\Units\Distance;
use \Marando\Units\Velocity;
use \Marando\Units\Angle;
use \Marando\Units\Time;
use \Marando\IAU\IAU;
use \Marando\AstroCoord\Geo;

/**
 * Represents an equatorial coordinate
 *
 * @param Frame $frame Reference frame
 * @param Epoch $epoch Observation epoch
 * @param Time  $ra    Right ascension
 * @param Angle $dec   Declination
 */
class Equat {

  use Traits\CopyTrait;

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate
   *
   * @param Frame    $frame Reference frame
   * @param Epoch    $epoch Observation epoch
   * @param Time     $ra    Right ascension
   * @param Angle    $dec   Declination
   * @param Distance $dist  Distance
   */
  public function __construct(Frame $frame, Epoch $epoch, Time $ra, Angle $dec,
          Distance $dist) {

    // Set reference frame and observation epoch
    $this->frame = $frame;
    $this->epoch = $epoch;

    // Set right ascension, declination and distance
    $this->ra   = $ra->setUnit('hsm');
    $this->dec  = $dec;
    $this->dist = $dist;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Reference frame
   * @var Frame
   */
  protected $frame;

  /**
   * Observation epoch
   * @var Epoch
   */
  protected $epoch;

  /**
   * Right ascension
   * @var Time
   */
  protected $ra;

  /**
   * Declination
   * @var Angle
   */
  protected $dec;

  /**
   * Distance
   * @var Distance
   */
  protected $dist;

  /**
   * If the coordinates are apparent
   * @var bool
   */
  protected $apparent;

  public function __get($name) {
    switch ($name) {
      case 'frame':
      case 'epoch':
      case 'ra':
      case 'dec':
      case 'dist':
        return $this->{$name};
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Returns the apparent coordinates of this instance for a given location
   *
   * @param  Geo         $geo      Geographic observation location
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return static
   */
  public function apparent(Geo $geo = null, Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Check if instance has aleady been converte to apparent
    if ($this->apparent)
      return $this->copy();

    // Set up parameters requred by IAU apparent algorithm
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $pr    = 0;
    $pd    = 0;
    $px    = $this->dist->m > 0 ? $this->dist->toParallax()->rad : 1e-13;
    $rv    = 0;
    $utc1  = $this->epoch->toDate()->toUT1()->jd;
    $utc2  = 0;
    $dut1  = 0.155;
    $elong = $geo ? $geo->lon->rad : 0;
    $phi   = $geo ? $geo->lat->rad : 0;
    $hm    = 0;
    $xp    = 0;
    $yp    = 0;
    $phpa  = $pressure ? $pressure->mbar : 1000;
    $tc    = $temp ? $temp->C : 15;
    $rh    = $humidity ? $humidity : 0.7;
    $wl    = 0.55;

    // Run the conversion
    IAU::Atco13($rc, $dc, $pr, $pd, $px, $rv, $utc1, $utc2, $dut1, $elong, $phi,
            $hm, $xp, $yp, $phpa, $tc, $rh, $wl, $aob, $zob, $hob, $dob, $rob,
            $eo);

    // Copy this instance, and override the apparent RA and Decl
    $apparent           = $this->copy();
    $apparent->ra       = Angle::rad($rob)->toTime();
    $apparent->dec      = Angle::rad($dob);
    $apparent->apparent = true;

    // Return apparent coordinates
    return $apparent;
  }

  /**
   * Converts this instance to horizontal coordinates
   *
   * @param  Geo         $geo      Geographic observation location
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return Horiz
   */
  public function toHoriz(Geo $geo = null, Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Copy the apparent coordinates of this instance, and get observation date
    $apparent = $this->copy()->apparent($geo, $pressure, $temp, $humidity);
    $date     = $this->epoch->toDate();

    // Local apparant sidereal time and local hour angle
    $last = $date->gast($geo ? $geo->lon : null);
    $H    = $last->copy()->subtract($apparent->ra)->toAngle()->rad;

    // Get right ascension and declination as radians
    $α = $apparent->ra->toAngle()->rad;
    $δ = $apparent->dec->rad;

    // Get geographic longitude as radians
    $φ = $geo ? $geo->lat->rad : 0;
    $ψ = $geo ? $geo->lon->rad : 0;

    // Calculate alt/az
    $az  = atan(sin($H) / (cos($H) * sin($φ) - tan($δ) * cos($φ)));
    $alt = asin(sin($φ) * sin($δ) + cos($φ) * cos($δ) * cos($H));

    // Return new horizontal coordinate instance
    return new Horiz(Angle::rad($alt), Angle::rad($az)->norm());
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    if ($this->apparent)
      return "RA $this->ra Dec $this->dec ($this->epoch apparent)";
    else
      return "RA $this->ra Dec $this->dec ($this->frame)";
  }

}
