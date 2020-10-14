// @flow
import React from 'react';
import classNames from 'classnames';
import { withStyles } from '@material-ui/core/styles';
import { IconButton } from '@material-ui/core';

import type { IToolbarItemProps } from './types';

import styles from './styles';

const ToolbarItem = ({
  label, onClick, icon: Icon, classes, isActive,
}: IToolbarItemProps) => (
  <IconButton
    title={label}
    aria-label={label}
    onClick={onClick}
    disabled={isActive}
    className={
      classNames(
        classes.iconButton,
        { [classes.iconButtonActive]: isActive },
      )
    }
  >
    <Icon />
  </IconButton>
);

export default withStyles(styles)(ToolbarItem);
