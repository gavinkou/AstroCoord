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

use \Exception;
use \Marando\AstroCoord\Equatorial;
use \Marando\AstroDate\AstroDate;
use \Marando\Meeus\Nutation\Nutation;
use \Marando\Units\Angle;

/**
 * Represents an ecliptic coordinate which is referenced to the plane of the
 * ecliptic
 *
 * @property Angle $lon Longitude, λ or l
 * @property Angle $lat Latitude, β or b
 */
class Ecliptic {
  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new ecliptic coordinate from ecliptic longitude and latitude
   *
   * @param Angle $lon Longitude, λ or l
   * @param Angle $lat Latitude, β or b
   */
  public function __construct(Angle $lon, Angle $lat) {
    $this->lon = $lon;
    $this->lat = $lat;
  }

  // // // Static

  /**
   * Creates a new ecliptic coordinate from ecliptic longitude and latitude
   *
   * @param  Angle  $lon Longitude, λ or l
   * @param  Angle  $lat Latitude, β or b
   * @return static
   */
  public static function create(Angle $lon, Angle $lat) {
    return new static($lon, $lat);
  }

  /**
   * Creates a new ecliptic coordinate from an equatorial coordinate. Because
   * the conversion depends upon the obliquity of Earth's ecliptic, a date must
   * be provided to calculate that value for. Optionally the obliquity can be
   * manually specified.
   *
   * @param  Equatorial $eq   Equatorial coordinate
   * @param  AstroDate  $date Date coordinates refer to
   * @param  Angle      $obli Optional obliquity of Earth's eclitpic
   * @return static
   */
  public static function equatorial(Equatorial $eq, AstroDate $date,
          Angle $obli = null) {

    $ε = $obli;
    if ($date && $obli == null)
    // If no obliquity and a date, find the obliquity of that date
      $ε = Nutation::trueObliquity($date);

    // Get ra and decl as radians
    $α = $eq->ra->toAngle()->rad;
    $δ = $eq->dec->rad;

    // Conversion to ecliptic coordinates
    $λ = atan((sin($α) * cos($ε->rad) + tan($δ) * sin($ε->rad)) / cos($α));
    $β = asin(sin($δ) * cos($ε->rad) - cos($δ) * sin($ε->rad) * sin($α));

    // Return new ecliptic coordinate
    return new static(Angle::rad($λ), Angle::rad($β));
  }

  /**
   * Creates a new ecliptic coordinate from latitude and longitude expressed as
   * degrees
   *
   * @param  float  $lon Longitude, λ or l (in degrees)
   * @param  float  $lat Latitude, β or b (in degrees)
   * @return static
   */
  public static function deg($lon, $lat) {
    return new static(Angle::deg($lon), Angle::deg($lat));
  }

  /**
   * Creates a new ecliptic coordinate from latitude and longitude expressed as
   * radians
   *
   * @param  float  $lon Longitude, λ or l (in radians)
   * @param  float  $lat Latitude, β or b (in radians)
   * @return static
   */
  public static function rad($lon, $lat) {
    return new static(Angle::rad($lon), Angle::rad($lat));
  }

  /**
   * Creates a new ecliptic coordinate from degree, minute and second components
   * of longitude and latitude respectively
   *
   * @param  float  $λd Longitude, degrees
   * @param  float  $λm Longitude, minutes
   * @param  float  $λs Longitude, seconds
   * @param  float  $βd Latitude, degrees
   * @param  float  $βm Latitude, minutes
   * @param  float  $βs Latitude, seconds
   * @return static
   */
  public static function dmsdms($λd, $λm, $λs, $βd, $βm, $βs) {
    return new static(Angle::dms($λd, $λm, $λs), Angle::dms($βd, $βm, $βs));
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Longitude, λ or l
   * @var Angle
   */
  protected $lon;

  /**
   * Latitude, β or b
   * @var Angle
   */
  protected $lat;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'lon':
      case 'lat':
        return $this->{$name};

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Converts this instance to an equatorial coordinate. Because the conversion
   * depeonds upon the obliquity of Earth's ecliptic, a date must be provided to
   * calculate that value for. Optionally the obliquity can be manually
   * specified.
   *
   * @param  AstroDate  $date Date coordinates refer to
   * @param  Angle      $obli Optional obliquity of Earth's eclitpic
   * @return Equatorial       Resulting equatorial coordinate
   */
  public function toEquatorial(AstroDate $date, Angle $obli = null) {
    return Equatorial::ecliptic($this, $date, $obli);
  }

  public function celestPoleDist($north = true) {

  }

  /**
   * Explodes this instance to an array of longitude and latitude components
   * expressed as radians
   *
   * @return array
   */
  public function explodeRad() {
    return [$this->lon->rad, $this->lat->rad];
  }

  /**
   * Explodes this instance to an array of longitude and latitude components
   * expressed as degrees
   *
   * @return array
   */
  public function explodeDeg() {
    return [$this->lon->deg, $this->lat->deg];
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return "lon = {$this->lon}, lat = {$this->lat}";
  }

}
