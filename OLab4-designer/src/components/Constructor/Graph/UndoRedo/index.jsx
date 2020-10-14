// @flow
import React, { memo } from 'react';
import { connect } from 'react-redux';
import { IconButton } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import UndoIcon from '../../../../shared/assets/icons/undo.svg';
import RedoIcon from '../../../../shared/assets/icons/redo.svg';

import * as actions from '../../../../redux/map/action';
import type { IUndoRedoButtonsProps } from './types';
import styles, { Container } from './styles';

export const GraphUndoRedoButtons = ({
  classes, isUndoAvailable, isRedoAvailable, ACTION_REDO_MAP, ACTION_UNDO_MAP,
}: IUndoRedoButtonsProps) => (
  <Container>
    <IconButton
      aria-label="Undo Button"
      onClick={ACTION_UNDO_MAP}
      disabled={!isUndoAvailable}
      className={classes.undoRedo}
    >
      <UndoIcon />
    </IconButton>
    <IconButton
      aria-label="Redo Button"
      onClick={ACTION_REDO_MAP}
      disabled={!isRedoAvailable}
      className={classes.undoRedo}
    >
      <RedoIcon />
    </IconButton>
  </Container>
);

const mapStateToProps = ({ map: { undo, redo } }) => ({
  isUndoAvailable: !!undo.length,
  isRedoAvailable: !!redo.length,
});

const mapDispatchToProps = dispatch => ({
  ACTION_REDO_MAP: () => {
    dispatch(actions.ACTION_REDO_MAP());
  },
  ACTION_UNDO_MAP: () => {
    dispatch(actions.ACTION_UNDO_MAP());
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(memo(GraphUndoRedoButtons)));
