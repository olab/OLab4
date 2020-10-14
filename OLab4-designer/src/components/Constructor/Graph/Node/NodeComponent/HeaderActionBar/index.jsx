// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Fab } from '@material-ui/core';

import LockIcon from '../../../../../../shared/assets/icons/lock.svg';
import MinimizeIcon from '../../../../../../shared/assets/icons/minimize.svg';

import { ACTION_COLLAPSE, ACTION_LOCK } from '../../config';

import type { IHeaderActionBarProps } from './types';

import styles from './styles';

const HeaderActionBar = ({ classes }: IHeaderActionBarProps) => (
  <>
    <Fab data-active="true" data-action={ACTION_COLLAPSE} className={classes.actionBarButton}>
      <MinimizeIcon />
    </Fab>
    <Fab data-active="true" data-action={ACTION_LOCK} className={classes.actionBarButton}>
      <LockIcon />
    </Fab>
  </>
);

export default withStyles(styles)(HeaderActionBar);
