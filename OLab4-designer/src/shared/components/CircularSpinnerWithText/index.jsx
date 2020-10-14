// @flow
import React from 'react';
import classNames from 'classnames';
import { withStyles } from '@material-ui/core/styles';
import { Typography, CircularProgress } from '@material-ui/core';

import type { CircularSpinnerWithTextProps as IProps } from './types';

import { DEFAULT_LABEL } from './config';

import { getSize } from './utils';

import styles, { ProgressWrapper } from './styles';

const CircularSpinnerWithText = ({
  classes, small = false, medium = true, large = false,
  centered = false, text = DEFAULT_LABEL,
}: IProps) => (
  <ProgressWrapper centered={centered}>
    <CircularProgress size={getSize(small, medium, large)} />
    <Typography variant="caption" className={classNames(classes.spinnerCaption, centered && classes.centeredText)}>
      {text}
    </Typography>
  </ProgressWrapper>
);


export default withStyles(styles)(CircularSpinnerWithText);
