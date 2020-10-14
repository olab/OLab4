// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { IconButton, InputLabel } from '@material-ui/core';
import { SwapVertRounded as ReverseIcon } from '@material-ui/icons';

import type { IChangeDirectionProps } from './types';

import styles from './styles';

const ChangeDirection = ({
  label, title, size, classes, onClick,
}: IChangeDirectionProps) => (
  <div>
    <InputLabel>{label}</InputLabel>
    <IconButton
      aria-label={title}
      title={title}
      size={size}
      onClick={onClick}
      classes={{
        root: classes.reverseIcon,
      }}
    >
      <ReverseIcon />
    </IconButton>
  </div>
);

export default withStyles(styles)(ChangeDirection);
