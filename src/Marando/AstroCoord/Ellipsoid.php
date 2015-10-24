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

use \Marando\Units\Distance;

/**
 * @property Distance $eqRadius
 * @property Distance $flattening
 * @property Distance $polarRadius
 * @property float    $eccentricity
 */
class Ellipsoid {

  //----------------------------------------------------------------------------
  // Constants
  //----------------------------------------------------------------------------

  const EARTH_ROTATIONAL_VELOCITY_1996_5 = 7.292114992e-5;

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  public function __construct(Distance $eqRadius, $flattening) {
    $this->eqRadius   = $eqRadius;
    $this->flattening = $flattening;
  }

  // // // Static

  public static function create(Distance $eqRadius, $flattening) {
    return new static($eqRadius, $flattening);
  }

  public static function Earth1976IAU() {
    return new static(Distance::km(6378.14), 1 / 298.257);
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  protected $eqRadius;
  protected $flattening;
  protected $eccentricity;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'eqRadius':
      case 'flattening':
      case 'eccentricity':
        return $this->{$name};

      case 'polarRadius':
        return $this->getPolarRadius();

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Calculates the parallax constants ρ sin φ' and ρ cos φ'
   *
   * @param  Geographic $geo    Geographic location of observation
   * @param  Distance   $height Height in meters from sea level
   * @return array              [ρ sin φ', ρ cos φ']
   */
  public function parallaxConst(Geographic $geo, Distance $height = null) {
    $a = $this->eqRadius->m;
    $b = $this->polarRadius->m;
    $φ = $geo->lat->rad;
    $h = $height == null ? 0 : $height->m;

    $u = atan(($b / $a) * tan($φ));

    $ρSinφ´ = ($b / $a) * sin($u) + ($h / $a) * sin($φ);
    $ρCosφ´ = cos($u) + ($h / $a) * cos($φ);

    return [$ρSinφ´, $ρCosφ´];
  }

  // // // Protected

  protected function getPolarRadius() {
    return Distance::km($this->eqRadius->km * (1 - $this->flattening));
  }

}
