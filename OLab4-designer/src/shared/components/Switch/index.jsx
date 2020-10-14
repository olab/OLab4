// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Switch as MaterialSwitch, FormControlLabel, InputLabel } from '@material-ui/core';

import type { ISwitchProps } from './types';

import styles from './styles';

const Switch = ({
  name, label, labelPlacement, classes, checked = false, disabled = false, onChange,
}: ISwitchProps) => (
  <FormControlLabel
    label={(
      <InputLabel>{label}</InputLabel>
    )}
    labelPlacement={labelPlacement}
    classes={{
      root: classes.formControlRoot,
    }}
    control={(
      <MaterialSwitch
        classes={{
          switchBase: classes.iOSSwitchBase,
          bar: classes.iOSBar,
          icon: classes.iOSIcon,
          iconChecked: classes.iOSIconChecked,
          checked: classes.iOSChecked,
        }}
        checked={checked}
        disabled={disabled}
        onChange={(e: Event, checkedVal: boolean): Function => onChange(e, checkedVal, name)}
        disableRipple
      />
    )}
  />
);

export default withStyles(styles)(Switch);
