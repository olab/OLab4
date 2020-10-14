// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { TextField } from '@material-ui/core';

import type { IOutlinedInputProps } from './types';

import styles from './styles';

const OutlinedInput = ({
  name,
  label,
  classes,
  value = '',
  type = 'text',
  placeholder = '',
  onChange,
  onFocus,
  setRef,
  fullWidth = false,
  disabled = false,
  readOnly = false,
}: IOutlinedInputProps) => (
  <TextField
    type={type}
    name={name}
    label={label}
    value={value}
    placeholder={placeholder}
    onChange={onChange}
    onFocus={onFocus}
    margin="none"
    variant="outlined"
    disabled={disabled}
    InputProps={{
      classes: {
        input: classes.input,
      },
      readOnly,
      inputRef: setRef,
    }}
    InputLabelProps={{
      classes: {
        root: classes.focusedLabel,
      },
    }}
    fullWidth={fullWidth}
  />
);

export default withStyles(styles)(OutlinedInput);
