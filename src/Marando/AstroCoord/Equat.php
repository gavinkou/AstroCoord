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
use \Marando\AstroDate\Epoch;
use \Marando\IAU\IAU;
use \Marando\IAU\iauASTROM;
use \Marando\Units\Angle;
use \Marando\Units\Distance;
use \Marando\Units\Pressure;
use \Marando\Units\Temperature;
use \Marando\Units\Time;

/**
 * Represents an equatorial coordinate
 *
 * @param Frame $frame Reference frame
 * @param Epoch $epoch Observation epoch
 * @param Time  $ra    Right ascension
 * @param Angle $dec   Declination
 * @param Geo   $obsrv Geographic observation location
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

  /**
   * Geographic observation location
   * @var Geo
   */
  protected $obsrv;

  protected $astrom;

  public function __get($name) {
    switch ($name) {
      case 'frame':
      case 'epoch':
      case 'ra':
      case 'dec':
      case 'dist':
      case 'obsrv':
        return $this->{$name};
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      case 'obsrv':
        $this->{$name} = $value;
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Returns the apparent coordinates of this instance. Optionally weather
   * parameters can be supplied to apply atmospheric refraction to the result.
   *
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return static
   */
  public function apparent(/* Geo $geo = null, */ Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Check if aleady converted to apparent, and return that if so
    if ($this->apparent)
      return $this->copy();

    // If no topographic location set, return apparent geocentric
    if ($this->obsrv == false || $this->obsrv == null)
      return $this->IRCStoApparentGeo();

    // If topographic location set, but no weather, return topographic apparent
    if ($this->obsrv && !($pressure || $temp || $humidity))
      return $this->IRCStoTopo();

    // If topographic location set, and weather, return topographic observed
    if ($this->obsrv && ($pressure || $temp || $humidity))
      return $this->IRCStoObserved();

    throw new Exception('An error has occured finding apparent coordinates');
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
  public function toHoriz(/* Geo $geo = null, */Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    return $this->IRCStoObserved('h', $pressure, $temp, $humidity);




    $radec = $this->astrom ? $this->astrom : $this;

    $geo = $this->obsrv ? $this->obsrv : $geo;

    // Set up parameters requred by IAU apparent algorithm
    $rc    = $radec->ra->toAngle()->rad;
    $dc    = $radec->dec->rad;
    $pr    = 0;
    $pd    = 0;
    $px    = $radec->dist->m > 0 ? $radec->dist->toParallax()->rad : 1e-13;
    $rv    = 0;
    $utc1  = $radec->epoch->toDate()->toUTC()->jd;
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


    return new Horiz(Angle::rad(deg2rad(90) - $zob), Angle::rad($aob));


    return;
    // Copy the apparent coordinates of this instance, and get observation date
    //$apparent = $this->copy()->apparent($geo, $pressure, $temp, $humidity);
    $date = $this->epoch->toDate();

    // Local apparant sidereal time and local hour angle
    $last = $date->gast($geo ? $geo->lon : null);
    $H    = $last->copy()->subtract($radec->ra)->toAngle()->rad;

    // Get right ascension and declination as radians
    $α = $radec->ra->toAngle()->rad;
    $δ = $radec->dec->rad;

    // Get geographic longitude as radians
    $φ = $geo ? $geo->lat->rad : 0;
    $ψ = $geo ? $geo->lon->rad : 0;

    // Calculate alt/az
    $az  = atan(sin($H) / (cos($H) * sin($φ) - tan($δ) * cos($φ)));
    $alt = asin(sin($φ) * sin($δ) + cos($φ) * cos($δ) * cos($H));

    // Return new horizontal coordinate instance
    return new Horiz(Angle::rad($alt), Angle::rad($az)->norm());
  }

  // // // Protected

  /**
   * Performs a [IRCS -> geocentric apparent] transformation for this instance
   * @return static
   */
  protected function IRCStoApparentGeo() {
    // Instance initial properties
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $date1 = $this->epoch->jd;
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = 0;

    // ICRS -> CIRS (geocentric observer)
    IAU::Atci13($rc, $dc, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // CIRS -> ICRS (astrometric)
    IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);

    // ICRS (astrometric) -> CIRS (geocentric observer)
    IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // Apparent place
    $ra = $ri - $eo;
    $da = $di;

    // Copy this instance, and override the apparent RA and Decl
    $apparent           = $this->copy();
    $apparent->ra       = Angle::rad($ra)->toTime();
    $apparent->dec      = Angle::rad($da);
    $apparent->apparent = true;

    // Return apparent coordinates
    return $apparent;
  }

  protected function IRCStoTopo($type = 'e') {
    // Same as topo but iwth no weather
    return $this->IRCStoObserved($type);
  }

  protected function IRCStoObserved($type = 'e', Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Instance initial properties
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $date1 = $this->epoch->jd;
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = 0;
    $utc1  = $this->epoch->toDate()->toUTC()->jd;
    $dut1  = .155;
    $elong = $this->obsrv ? $this->obsrv->lon->rad : 0;
    $phi   = $this->obsrv ? $this->obsrv->lat->rad : 0;
    $hm    = 0; //$this->obsrv->height->m;
    $xp    = 0;
    $yp    = 0;
    $phpa  = $pressure ? $pressure->mbar : 0;
    $tc    = $temp ? $temp->c : 0;
    $rh    = $humidity ? $humidity : 0;
    $wl    = 0.55;

    // ICRS -> CIRS (geocentric observer)
    IAU::Atci13($rc, $dc, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // CIRS -> ICRS (astrometric)
    IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);

    // ICRS (astrometric) -> CIRS (geocentric observer)
    IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // Apparent place ?
    //$ri = $ri - $eo;
    //$di = $di;
    //
    // CIRS -> topocentric
    IAU::Atio13($ri, $di, $utc1, 0, $dut1, $elong, $phi, $hm, $xp, $yp, $phpa,
            $tc, $rh, $wl, $aob, $zob, $hob, $dob, $rob);

    if ($type == 'e') {
      // Copy this instance, and override the apparent RA and Decl
      $topocentric           = $this->copy();
      $topocentric->ra       = Angle::rad($rob)->toTime();
      $topocentric->dec      = Angle::rad($dob);
      $topocentric->apparent = true;

      // Return apparent coordinates
      return $topocentric;
    }
    else {
      $horiz = new Horiz(Angle::rad(deg2rad(90) - $zob), Angle::rad($aob));
      return $horiz;
    }
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
    $rD       = sprintf($drFormat, $this->ra->h);
    $rM       = sprintf($mFormat, abs($this->ra->m));
    $rS       = sprintf($sFormat, abs($this->ra->s));
    $dD       = sprintf($ddFormat, $this->dec->d);
    $dM       = sprintf($mFormat, abs($this->dec->m));
    $dS       = sprintf($sFormat, abs($this->dec->s));
    $rmic     = str_replace('0.', '', round(abs($this->ra->micro), 3));
    $rmic     = str_pad($rmic, 3, '0', STR_PAD_RIGHT);
    $dmic     = str_replace('0.', '',
            round(abs(intval($this->dec->s) - $this->dec->s), 3));
    $dmic     = str_pad($dmic, 3, '0', STR_PAD_RIGHT);
    $dist     = ''; //$dist = $this->dist ? " Dist {$this->dist}" : '';

    $frame = $this->apparent ? "$this->epoch apparent" : "$this->frame";

    return "RA {$rD}ʰ{$rM}ᵐ{$rS}ˢ.{$rmic} Dec {$dD}°{$dM}'{$dS}\".{$dmic} ({$frame})";



    if ($this->apparent)
      return "RA $this->ra Dec $this->dec ($this->epoch apparent)";
    else
      return "RA $this->ra Dec $this->dec ($this->frame)";
  }

}
