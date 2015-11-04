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

use \Marando\AstroDate\Epoch;
use \Marando\AstroDate\AstroDate;
use \Marando\Units\Distance;
use \Marando\Units\Velocity;
use \Marando\Units\Angle;
use \Marando\Units\Time;
use \Marando\IAU\IAU;
use \Marando\AstroCoord\Geo;

class Equat {

  use Traits\CopyTrait;

  protected $frame;
  protected $epoch;
  protected $ra;
  protected $dec;
  protected $apparent;

  public function __construct(Frame $frame, Epoch $epoch, Time $ra, Angle $dec,
          Distance $dist) {

    $this->frame = $frame;
    $this->epoch = $epoch;
    $this->ra    = $ra->setUnit('hsm');
    $this->dec   = $dec;
  }

  public function __get($name) {
    switch ($name) {
      case 'ra':
      case 'dec':
        return $this->{$name};
    }
  }

  public function apparent(Geo $geo = null) {
    if ($this->apparent)
      return $this->copy();

    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $pr    = 0;
    $pd    = 0;
    $px    = 0; //$this->dist->toParallax()->arcsec;
    $rv    = 0;
    $utc1  = $this->epoch->toDate()->toUT1()->jd;
    $utc2  = 0;
    $dut1  = 0.155;
    $elong = $geo ? $geo->lon->rad : 0;
    $phi   = $geo ? $geo->lat->rad : 0;
    $hm    = 0;
    $xp    = 0;
    $yp    = 0;
    $phpa  = 731;
    $tc    = 12.8;
    $rh    = 0.7;
    $wl    = 0.55;

    IAU::Atco13($rc, $dc, $pr, $pd, $px, $rv, $utc1, $utc2, $dut1, $elong, $phi,
            $hm, $xp, $yp, $phpa, $tc, $rh, $wl, $aob, $zob, $hob, $dob, $rob,
            $eo);

    $copy = $this->copy();

    $copy->ra  = Angle::rad($rob)->toTime();
    $copy->dec = Angle::rad($dob);

    $copy->apparent = true;
    return $copy;
  }

  public function toHoriz(Geo $geo) {
    $apparent = $this->copy()->apparent($geo);
    $date     = $this->epoch->toDate();

    // Local apparant sidereal time and local hour angle
    $last = $date->gast($geo->lon);
    $H    = $last->copy()->subtract($apparent->ra)->toAngle()->rad;

    // Get right ascension and declination as radians
    $α = $apparent->ra->toAngle()->rad;
    $δ = $apparent->dec->rad;

    // Get geographic longitude as radians
    $φ = $geo->lat->rad;
    $ψ = $geo->lon->rad;

    // Calculate alt/az
    $az  = atan(sin($H) / (cos($H) * sin($φ) - tan($δ) * cos($φ)));
    $alt = asin(sin($φ) * sin($δ) + cos($φ) * cos($δ) * cos($H));

    return new Horiz(Angle::rad($alt), Angle::rad($az)->norm());
  }

  public function __toString() {
    if ($this->apparent)
      return "RA $this->ra Dec $this->dec ($this->epoch apparent)";
    else
      return "RA $this->ra Dec $this->dec ($this->frame)";
  }

}

/**
 * ->apparent(Date, temp, etc...)
 *    - calls IAU func to make apparent
 *    - stores parameters
 *    - modifies class to store apparent position KEEPS old astrometric
 *    - if called again with no parameters returns apparent calc before
 *    - if called again with diff params, uses old astro to recompute
 *
 * ->astrometric(Date, temp, etc...)
 *    - if called with no params and instance is apparent, returns the old astro one
 *    - if instance is not astrometric, and params computes astrometric
 *    - if instance is not astrometric, and no params throws error?
 *
 */