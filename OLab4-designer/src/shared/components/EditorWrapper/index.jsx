// @flow
import React from 'react';
import { withRouter } from 'react-router-dom';
import { withStyles } from '@material-ui/core/styles';
import {
  Grid, Typography, IconButton,
} from '@material-ui/core';
import { ArrowBackRounded as ArrowBackIcon } from '@material-ui/icons';
import TitledButton from '../TitledButton';
import { redirectToSO } from './utils';

import type { IEditorWrapperProps } from './types';

import styles, { HeadingWrapper, Paper, Container } from './styles';

const EditorWrapper = ({
  children,
  classes,
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
        <Grid item xs={2} style={{ minWidth: '160px' }}>
          {(typeof onRevert !== 'undefined') && (
            <TitledButton
              title="Reload from server"
              className={classes.submit}
              onClick={onRevert}
              disabled={isDisabled}
              label="Revert"
            />
          )}
        </Grid>
        <Grid item xs={2} style={{ minWidth: '160px' }}>
          <TitledButton
            title="Save changes to server"
            color="primary"
            label={isEditMode ? 'Save' : 'Create'}
            type="submit"
            className={classes.submit}
            onClick={onSubmit}
          />
        </Grid>
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
