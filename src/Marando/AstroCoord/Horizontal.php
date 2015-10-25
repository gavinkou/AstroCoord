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

use \Marando\AstroCoord\Geographic;
use \Marando\Units\Angle;
use \Marando\AstroDate\AstroDate;
use \Marando\Units\Distance;

/**
 * Represents a horizontal coordinate which is referenced to the local horizon
 * of a point on earth
 *
 * @property Angle $alt Altitude, angular height above horizon
 * @property Angle $az  Azimuth, measured westward from the South
 */
class Horizontal implements ITopographic {

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------


  public function __construct(Angle $alt, Angle $az) {
    $this->alt = $alt;
    $this->az  = $az;
  }

  // // // Static

  public static function create(Angle $alt, Angle $az) {
    return new static($alt, $az);
  }

  public static function equatorial(Equatorial $eq, Geographic $geo,
          AstroDate $date) {

    // Apparent sidereal time at Greenwich
    $st = $date->gast();

    // Calculate local hour angle of object
    $H = $st->subtract($geo->lon->toTime())->subtract($eq->ra)->toAngle()->rad;

    // Get right ascension and declination as radians
    $α = $eq->ra->toAngle()->rad;
    $δ = $eq->dec->rad;

    // Get geographic longitude as radians
    $φ = $geo->lat->rad;
    $ψ = $geo->lon->rad;

    // Calculate alt/az
    $az  = atan(sin($H) / (cos($H) * sin($φ) - tan($δ) * cos($φ)));
    $alt = asin(sin($φ) * sin($δ) + cos($φ) * cos($δ) * cos($H));

    return static::rad($alt, $az);
  }

  public static function deg($alt, $az) {
    return new static(Angle::deg($alt), Angle::deg($az));
  }

  public static function rad($alt, $az) {
    return new static(Angle::rad($alt), Angle::rad($az));
  }

  public static function dmsdms($hd, $hm, $hs, $ad, $am, $as) {
    return new static(Angle::dms($hd, $hm, $hs), Angle::dms($ad, $am, $as));
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  protected $alt;
  protected $az;
  protected $refracted = false;
  protected $topo      = false;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'alt':
      case 'az':
        return $this->{$name};

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   *
   * @param Geographic $geo
   * @param AstroDate $date
   * @return Equatorial
   */
  public function toEquatorial(Geographic $geo, AstroDate $date) {
    return Equatorial::horizontal($this, $geo, $date);
  }

  public function geocentr(Geographic $geo, AstroDate $date) {
    return $this->toEquatorial($geo, $date)->topo($geo, $date);
  }

  public function topo(Geographic $geo, AstroDate $date) {
    return $this->toEquatorial($geo, $date)->geocentr($geo, $date);
  }

  // // set flag that warns or throws exception if refration is not enabled
  // and topo is requested, or
  // if geocentr is requested but hasnt been defracted

  /**
    public function topo(Distance $d, Geographic $geo = null, Distance $h = null) {
    // Find appropriate geographic coordinates
    if ($geo == null)
    if ($this->geo)
    $geo = $this->geo;
    else
    throw new \InvalidArgumentException('No geographic coordinates.');



    }

    public function geocentr(Distance $d, Geographic $geo = null,
    Distance $h = null) {
    if ($geo == null)
    if ($this->geo)
    $geo = $this->geo;
    else
    throw new \InvalidArgumentException('No geographic coordinates.');
    }
   *
   */
  public function refract(Pressure $p = null, Temperature $t = null) {
    if ($this->refracted == true)
      return $this;

    // expected pressure and temperature


    $this->refracted = true;
    return $this;
  }

  public function defract(Pressure $p = null, Temperature $t = null) {
    if ($this->refracted == false)
      return $this;

    // measured pressure and temperature


    $this->refracted = false;
    return $this;
  }

  public function explodeRad() {
    return [$this->alt->rad, $this->az->rad];
  }

  public function explodeDeg() {
    return [$this->alt->deg, $this->az->deg];
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return "alt = {$this->alt}, az = {$this->az}";
  }

}
