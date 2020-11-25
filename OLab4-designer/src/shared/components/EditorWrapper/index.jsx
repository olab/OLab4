// @flow
import React from 'react';
import { withRouter } from 'react-router-dom';
import { withStyles } from '@material-ui/core/styles';
import {
  Button, Grid, Typography, IconButton,
} from '@material-ui/core';
import { ArrowBackRounded as ArrowBackIcon } from '@material-ui/icons';

import { redirectToSO } from './utils';

import type { IEditorWrapperProps } from './types';

import styles, { HeadingWrapper, Paper, Container } from './styles';

const EditorWrapper = ({
  children,
  classes,
  dataChanged,
  hasBackButton,
  history,
  isDisabled,
  isEditMode,
  onRevert,
  onSubmit,
  scopedObject,
}: IEditorWrapperProps) => (
  <Grid container component="main" className={classes.root}>
    <HeadingWrapper>

      <Grid container spacing={3}>
        <Grid item xs={8}>
          <div className={classes.headerLabel}>
            {(hasBackButton) && (
              <>
                <IconButton
                  aria-label="Back To Object List"
                  title="Back To Object List"
                  onClick={(): void => redirectToSO(history, scopedObject)}
                >
                  <ArrowBackIcon className={classes.arrow} />
                </IconButton>
              </>
            )}
            <Typography variant="h4" className={classes.title}>
              {`${isEditMode ? 'EDIT' : 'ADD NEW'} ${scopedObject.toUpperCase()}`}
            </Typography>
          </div>
        </Grid>
        {dataChanged() && (
          <>
            <Grid item xs={2} style={{ minWidth: '160px' }}>
              {(typeof onRevert !== 'undefined') && (
                <Button
                  variant="contained"
                  className={classes.submit}
                  onClick={onRevert}
                  disabled={isDisabled}
                >
                  Revert
                </Button>
              )}
            </Grid>
            <Grid item xs={2} style={{ minWidth: '160px' }}>
              <Button
                type="submit"
                variant="contained"
                color="primary"
                className={classes.submit}
                onClick={onSubmit}
                disabled={isDisabled}
              >
                {isEditMode ? 'Update' : 'Create'}
              </Button>
            </Grid>

          </>
        )}
      </Grid>
    </HeadingWrapper>
    <Container>
      <Paper>
        {children}
      </Paper>
    </Container>
  </Grid>
);

export default withStyles(styles)(
  withRouter(EditorWrapper),
);
