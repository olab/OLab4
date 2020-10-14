// @flow
import React from 'react';

import Slider from '../../../../shared/components/Slider';
import OutlinedInput from '../../../../shared/components/OutlinedInput';

import { EDITORS_FIELDS } from '../../config';
import { DEFAULT_WIDTH, DEFAULT_HEIGHT } from '../config';

import type { IMultiLineLayoutProps } from './types';

import { FieldLabel } from '../../styles';

const MultiLineLayout = ({
  placeholder, width, height, isFieldsDisabled, onInputChange, onSliderChange,
}: IMultiLineLayoutProps) => (
  <>
    <FieldLabel>
      {EDITORS_FIELDS.PLACEHOLDER}
      <OutlinedInput
        name="placeholder"
        placeholder={EDITORS_FIELDS.PLACEHOLDER}
        value={placeholder}
        onChange={onInputChange}
        disabled={isFieldsDisabled}
        fullWidth
      />
    </FieldLabel>
    <FieldLabel>
      {EDITORS_FIELDS.WIDTH}
      <Slider
        name="width"
        value={width}
        min={DEFAULT_WIDTH.MIN}
        max={DEFAULT_WIDTH.MAX}
        step={DEFAULT_WIDTH.STEP}
        onChange={onSliderChange}
        disabled={isFieldsDisabled}
      />
    </FieldLabel>
    <FieldLabel>
      {EDITORS_FIELDS.HEIGHT}
      <Slider
        name="height"
        value={height}
        min={DEFAULT_HEIGHT.MIN}
        max={DEFAULT_HEIGHT.MAX}
        step={DEFAULT_HEIGHT.STEP}
        onChange={onSliderChange}
        disabled={isFieldsDisabled}
      />
    </FieldLabel>
  </>
);

export default MultiLineLayout;
