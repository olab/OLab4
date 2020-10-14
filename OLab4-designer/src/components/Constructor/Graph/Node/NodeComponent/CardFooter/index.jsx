// @flow
import React from 'react';
import classNames from 'classnames';
import { withStyles } from '@material-ui/core/styles';
import { Fab } from '@material-ui/core';

import AddIcon from '../../../../../../shared/assets/icons/add_node.svg';
import LinkIcon from '../../../../../../shared/assets/icons/link.svg';

import { ACTION_ADD, ACTION_LINK } from '../../config';

import type { ICardFooterProps } from './types';

import styles, { Wrapper } from './styles';

const CardFooter = ({ classes, isLinked }: ICardFooterProps) => (
  <Wrapper className="card-footer">
    <Fab
      data-active="true"
      data-action={ACTION_ADD}
      className={classes.fab}
    >
      <AddIcon />
    </Fab>
    <Fab
      data-active="true"
      data-action={ACTION_LINK}
      className={classNames(
        classes.fab,
        { [classes.linkIcon]: isLinked },
      )}
      disableRipple
    >
      <LinkIcon />
    </Fab>
  </Wrapper>
);

export default withStyles(styles)(CardFooter);
