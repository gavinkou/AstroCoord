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

use \Marando\AstroCoord\Geo;
use \Marando\AstroCoord\Horiz;
use \Marando\AstroDate\AstroDate;
use \Marando\IAU\IAU;
use \Marando\Units\Angle;
use \Marando\Units\Distance;
use \Marando\Units\Time;
use \Marando\IAU\iauRefEllips;

/**
 * Represents an equatorial coordinate
 *
 * @param Time     $ra   Right ascension
 * @param Angle    $dec  Declination
 * @param Distance $dist Distance
 */
class Equat {

  use \Marando\AstroCoord\Traits\CopyTrait;

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate
   *
   * @param Time     $ra   Right ascension
   * @param Angle    $dec  Declination
   * @param Distance $dist Distance
   */
  public function __construct(Time $ra, Angle $dec, Distance $dist = null) {

    // Set right ascension, declination and distance
    $this->ra   = $ra->setUnit('hms');
    $this->dec  = $dec;
    $this->dist = $dist;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

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

  public function __get($name) {
    switch ($name) {
      case 'ra':
      case 'dec':
      case 'dist':
        return $this->{$name};
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  public function toCartesian() {
    // Spherical -> cartesian position vector
    $p = [];
    IAU::S2p($this->ra->toAngle()->rad, $this->dec->rad, $this->dist->au, $p);

    $x = Distance::au($p[0]);
    $y = Distance::au($p[1]);
    $z = Distance::au($p[2]);

    return new Cartesian($x, $y, $z);
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    $drFormat = "%+03.0f";
    $ddFormat = "%+03.0f";
    $mFormat  = "%02.0f";
    $sFormat  = "%02.0f";

    $rD = sprintf($drFormat, $this->ra->h);
    $rM = sprintf($mFormat, abs($this->ra->m));
    $rS = sprintf($sFormat, abs($this->ra->s));

    $dD = sprintf($ddFormat, abs($this->dec->d));
    $dM = sprintf($mFormat, abs($this->dec->m));
    $dS = sprintf($sFormat, abs($this->dec->s));

    $rmic = str_replace('0.', '', round(abs($this->ra->micro), 3));
    $rmic = str_pad($rmic, 3, '0', STR_PAD_RIGHT);

    $dmic = str_replace('0.', '',
            round(abs(intval($this->dec->s) - $this->dec->s), 3));
    $dmic = str_pad($dmic, 3, '0', STR_PAD_RIGHT);

    $dist='';//$dist = $this->dist ? " Dist {$this->dist}" : '';
    return "RA {$rD}ʰ{$rM}ᵐ{$rS}ˢ.{$rmic} Dec {$dD}°{$dM}'{$dS}\".{$dmic}{$dist}";
  }

}
