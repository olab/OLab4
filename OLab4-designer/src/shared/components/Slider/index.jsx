// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { InputLabel } from '@material-ui/core';
import { Slider as MaterialSlider } from '@material-ui/lab';

import type { ISliderProps } from './types';

import styles, { SliderWrapper, SliderValue } from './styles';

const Slider = ({
  label, name, value, classes, min, max, step, disabled = false, onChange,
}: ISliderProps) => (
  <div>
    {label && <InputLabel>{label}</InputLabel>}
    <SliderWrapper>
      <MaterialSlider
        name={name || ''}
        classes={{ container: classes.slider }}
        value={value}
        onChange={(e: Event, val: number) => onChange(e, val, name)}
        min={min}
        max={max}
        step={step}
        disabled={disabled}
      />
      <SliderValue>{value}</SliderValue>
    </SliderWrapper>
  </div>
);

export default withStyles(styles)(Slider);
