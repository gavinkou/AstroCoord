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
use \Marando\AstroCoord\Ecliptic;
use \Marando\AstroCoord\ITopographic;
use \Marando\AstroDate\AstroDate;
use \Marando\Meeus\Nutation\Nutation;
use \Marando\Units\Angle;
use \Marando\Units\Distance;
use \Marando\Units\Time;

/**
 * Represents an equatorial coordinate which is referenced to the Earth's axis
 * of rotation
 *
 * @property Time     $ra   Right Ascension, ra or α
 * @property Angle    $dec  Declination, dec or δ
 * @property Distance $dist Distance, Δ or r
 */
class Equatorial implements ITopographic {
  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate instance from a right ascension
   * espressed as time, and a declination expressed in degrees
   *
   * @param Time  $ra  Right ascension, ra or α
   * @param Angle $dec Declination, dec or δ
   */
  public function __construct(Time $ra, Angle $dec) {
    $this->ra  = $ra;
    $this->dec = $dec;
  }

  // // // Static

  /**
   * Creates a new equatorial coordinate instance from a right ascension
   * espressed as time, and a declination expressed in degrees
   *
   * @param  Time   $ra  Right ascension, ra or α
   * @param  Angle  $dec Declination, dec or δ
   * @return static
   */
  public static function create(Time $ra, Angle $dec) {
    return new static($ra, $dec);
  }

  /**
   * Creates a new equatorial coordinate from an ecliptic coordinate. Because
   * the conversion depends upon the obliquity of Earth's ecliptic, a date must
   * be provided to calculate that value for. Optionally the obliquity can be
   * manually specified.
   *
   * @param  Ecliptic  $ecl  Ecliptic coordinate
   * @param  AstroDate $date Date coordinates refer to
   * @param  Angle     $obli Optional obliquity of Earth's eclitpic
   * @return static
   */
  public static function ecliptic(Ecliptic $ecl, AstroDate $date,
          Angle $obli = null) {

    $ε = $obli;
    if ($date && $obli == null)
    // If no obliquity and a date, find the obliquity of that date
      $ε = Nutation::trueObliquity($date);

    // Ecliptic longitude and latitude as radians
    $λ = $ecl->lon->rad;
    $β = $ecl->lat->rad;

    // Conversion to ra and declination
    $α = atan2(sin($λ) * cos($ε->rad) - tan($β) * sin($ε->rad), cos($λ));
    $δ = asin(sin($β) * cos($ε->rad) + cos($β) * sin($ε->rad) * sin($λ));

    // Return new Eqatorial instance
    return static::rad($α, $δ);
  }

  public static function horizontal(Horizontal $h) {

  }

  public static function galactic(Galactic $g) {

  }

  /**
   * Creates a new equatorial coordinate from a right ascension and declination
   * both expressed as angles
   *
   * @param  Angle  $ra  Right ascension, as angle
   * @param  Angle  $dec Declination, as angle
   * @return static
   */
  public static function angles(Angle $ra, Angle $dec) {
    return new static($ra->toTime(), $dec);
  }

  /**
   * Creates a new equatorial coordinate from a right ascension and declination
   * both expressed as degrees
   *
   * @param  float  $ra  Right ascension, in degrees
   * @param  float  $dec Declination, in degrees
   * @return static
   */
  public static function deg($ra, $dec) {
    return new static(Angle::deg($ra)->toTime(), Angle::deg($dec));
  }

  /**
   * Creates a new equatorial coordinate from a right ascension and declination
   * both expressed as radians
   *
   * @param  float  $ra  Right ascension, in radians
   * @param  float  $dec Declination, in radians
   * @return static
   */
  public static function rad($ra, $dec) {
    return new static(Angle::rad($ra)->toTime(), Angle::rad($dec));
  }

  /**
   * Creates a new equatorial coordinate from hour, minute and second components
   * of right ascension and degree, minute and second components of declination
   *
   * @param type $αh Right ascension, hours
   * @param type $αm Right ascension, minutes
   * @param type $αs Right ascension, seconds
   * @param type $δd Declination, degrees
   * @param type $δm Declination, minutes
   * @param type $δs Declination, seconds
   * @return static
   */
  public static function hmsdms($αh, $αm, $αs, $δd, $δm, $δs) {
    return new static(Time::hms($αh, $αm, $αs), Angle::dms($δd, $δm, $δs));
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Right Ascension, ra or α
   * @var Time
   */
  protected $ra;

  /**
   * Declination, dec or δ
   * @var Angle
   */
  protected $dec;

  /**
   * True if the coordinates are topographic, false if they are geocentric
   * @var bool
   */
  protected $topo = false;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'ra':
      case 'dec':
        return $this->{$name};

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  public function dist(Distance $dist) {
    $this->dist = $dist;
    return $this;
  }

  /**
   * Converts this instance to an ecliptic coordinate. Because the conversion
   * depeonds upon the obliquity of Earth's ecliptic, a date must be provided to
   * calculate that value for. Optionally the obliquity can be manually
   * specified.
   *
   * @param  AstroDate  $date Date coordinates refer to
   * @param  Angle      $obli Optional obliquity of Earth's eclitpic
   * @return Ecliptic         Resulting ecliptic coordinate
   */
  public function toEcliptic(AstroDate $date, Angle $obli = null) {
    return Ecliptic::equatorial($this, $date, $obli);
  }

  /**
   *
   * @param AstroDate $date
   * @param Geographic $geo
   * @return Horizontal
   */
  public function toHorizontal(AstroDate $date, Geographic $geo) {
    return Horizontal::equatorial($this, $geo, $date);
  }

  public function toGalactic(Epoch $e) {

  }

  public function celestPoleDist($north = true) {

  }

  /**
   * Explodes this instance to an array of right ascension and declination
   * components expressed as radians
   *
   * @return array
   */
  public function explodeRad() {
    return [$this->ra->toAngle()->rad, $this->dec->rad];
  }

  /**
   * Explodes this instance to an array of right ascension and declination
   * components expressed as degrees
   *
   * @return array
   */
  public function explodeDeg() {
    return [$this->ra->toAngle()->deg, $this->dec->deg];
  }

  /**
   * Converts the geocentric coordinates of this instance to topographic
   * coordinates based on the provided geographic location and time
   *
   * @param  Geographic $geo  Topographic observation point
   * @param  AstroDate  $date Date of observation
   * @return Equatorial       Topographic equatorial coordinates
   */
  public function topo(Geographic $geo , AstroDate $date ) {
    // Check if coordinate is already topographic
    if ($this->topo == true)
      return $this;

    // Get the geocentric to topographic delta
    $topoΔ = $this->topoDelta($this->dist, $geo, $date);

    // Adjust coordinates from geocenter to topographic location
    $this->ra  = $this->ra->add($topoΔ->ra);
    $this->dec = $this->dec->add($topoΔ->dec);

    // Flag coordinate as topographic and return instance
    $this->topo = true;
    return $this;
  }

  /**
   * Converts the topographic coordinates of this instance to geocentric
   * coordinates based on the provided geographic location and time
   *
   * @param  Geographic $geo  Topographic observation point
   * @param  AstroDate  $date Date of observation
   * @return Equatorial       Geocentric equatorial coordinates
   */
  public function geocentr(Geographic $geo , AstroDate $date ) {
    // Check if coordinate is already geocentric
    if ($this->topo == false)
      return $this;

    // Get the geocentric to topographic delta
    $topoΔ = $this->topoDelta($this->dist, $geo, $date);

    // Adjust coordinates from geocenter to topographic location
    $this->ra  = $this->ra->subtract($topoΔ->ra);
    $this->dec = $this->dec->subtract($topoΔ->dec);

    // Flag coordinate as geocentric and return instance
    $this->topo = false;
    return $this;
  }

  // // // Protected

  /**
   * Calculates the delta components of right ascension and declination for
   * converting coordinates to and from topographic and geocentric vantage
   * points
   *
   * @param  Distance   $dist Distance to target object
   * @param  Geographic $geo  Topographic observation point
   * @param  AstroDate  $date Date of observation
   * @return Equatorial       Resulting deltas
   */
  protected function topoDelta(Distance $dist, Geographic $geo, AstroDate $date) {
    // Parallax constants for provided geographic location
    $ρSinφ´ = $geo->pSinLat;
    $ρCosφ´ = $geo->pCosLat;

    // Right ascension and declination as radians
    $α = $this->ra->rad;
    $δ = $this->dec->rad;

    // Calculat parallax
    $π = deg2rad(8.794 / 3600 / $dist->au);

    // Sidereal time and local hour angle of right ascension
    $st = $date->gast();
    $H  = $st->add($geo->lon->toTime())->subtract($this->ra)->toAngle()->rad;

    // Calculate Δα
    $Δα = Angle::atan2(-$ρCosφ´ * sin($π) * sin($H),
                    cos($δ) - $ρCosφ´ * sin($π) * cos($H));

    // Calculate topographic declination
    $δ´ = Angle::atan2((sin($δ) - $ρSinφ´ * sin($π)) * cos($Δα->rad),
                    cos($δ) - $ρCosφ´ * sin($π) * cos($H));

    // Deduce Δδ from δ - δ´
    $Δδ = $δ´->subtract($this->dec);

    // Return delta components
    return static::angles($Δα, $Δδ);
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return "ra = {$this->ra->setUnit('hms')}, dec = {$this->dec}";
  }

}
