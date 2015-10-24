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

use \Marando\Units\Angle;
use \Marando\Units\Distance;

/**
 * @property Angle    $lat     Latitude, φ
 * @property Angle    $lon     Longitude, ψ or L
 * @property Distance $height  Height above sea level
 * @property float    $pSinLat Parallax constant ρ sin φ'
 * @property float    $pCosLat Parallax constant ρ cos φ'
 */
class Geographic {

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  public function __construct(Angle $lat, Angle $lon) {
    $this->lat    = $lat;
    $this->lon    = $lon;
  }

  // // // Static

  public static function create(Angle $lat, Angle $lon) {
    return new static($lat, $lon, $height);
  }

  public static function deg($lat, $lon) {
    return new static(Angle::deg($lat), Angle::deg($lon));
  }

  public static function rad($lat, $lon) {
    return new static(Angle::rad($lat), Angle::rad($lon));
  }

  public static function dmsdms($φd, $φm, $φs, $ψd, $ψm, $ψs) {
    return new static(Angle::dms($φd, $φm, $φs), Angle::dms($ψd, $ψm, $ψs));
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   *
   * @var Angle
   */
  protected $lat;

  /**
   *
   * @var Angle
   */
  protected $lon;

  /**
   * Height above sea level
   * @var Distance
   */
  protected $height;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'lat':
      case 'lon':
      case 'height':
        return $this->{$name};

      case 'pSinLat':
        return $this->getParallaxConst()[0];

      case 'pCosLat':
        return $this->getParallaxConst()[1];

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  public function height(Distance $height) {
    $this->height = $height;
    return $this;
  }

  // // // Protected

  protected function getParallaxConst(Distance $height = null) {
    $earth = Ellipsoid::Earth1976IAU();
    return $earth->parallaxConst($this, $height ? $height : $this->height);
  }

  // // // Overrides

  public function __toString() {
    if ($this->height)
      return "lat = {$this->lat}, lon = {$this->lon}, h = {$this->height}";
    else
      return "lat = {$this->lat}, lon = {$this->lon}";
  }

}
