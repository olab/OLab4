// @flow
import React, { PureComponent } from 'react';
import { withStyles } from '@material-ui/core/styles';
import {
  Button,
  Typography,
  ExpansionPanel as ExpansionPanelMaterialUI,
  ExpansionPanelDetails,
  ExpansionPanelSummary,
} from '@material-ui/core';
import {
  ExpandMore as ExpandMoreIcon,
  ArrowForward as ArrowForwardIcon,
} from '@material-ui/icons';

import { PANEL_NAMES } from './config';

import type { ExpansionPanelProps, ExpansionPanelState } from './types';

import styles, { ExpansionPanelWrapper } from './styles';

class ExpansionPanel extends PureComponent<ExpansionPanelProps, ExpansionPanelState> {
  state: ExpansionPanelState = {
    expandedPanel: null,
  };

  handleChange = (panelName: string): Function => (event: Event, expanded: boolean): void => {
    this.setState({
      expandedPanel: expanded
        ? panelName
        : null,
    });
  }

  render() {
    const { expandedPanel } = this.state;
    const {
      classes, showModal, onChoose, isDisabled,
    } = this.props;

    return (
      <ExpansionPanelWrapper>
        <ExpansionPanelMaterialUI
          expanded={expandedPanel === PANEL_NAMES.MANUAL}
          onChange={this.handleChange(PANEL_NAMES.MANUAL)}
        >
          <ExpansionPanelSummary expandIcon={<ExpandMoreIcon />}>
            <Typography className={classes.heading}>Manual Map Creation</Typography>
            <Typography className={classes.secondaryHeading}>More experienced authors</Typography>
          </ExpansionPanelSummary>
          <ExpansionPanelDetails>
            <Typography className={classes.content}>
              A single pre-populated root node (named ‘Start node’)
              is created and positioned in middle of Layout Editor window.
              <br />
              <br />
              Then you will be prompted for a Map name to save to before proceeding;
            </Typography>
            <Button
              variant="outlined"
              color="primary"
              size="small"
              aria-label="Create"
              classes={{ root: classes.fab }}
              onClick={onChoose}
              disabled={isDisabled}
            >
              Create Map
              <ArrowForwardIcon
                fontSize="small"
                classes={{ root: classes.icon }}
              />
            </Button>
          </ExpansionPanelDetails>
        </ExpansionPanelMaterialUI>
        <ExpansionPanelMaterialUI
          expanded={expandedPanel === PANEL_NAMES.FROM_TEMPLATE}
          onChange={this.handleChange(PANEL_NAMES.FROM_TEMPLATE)}
        >
          <ExpansionPanelSummary expandIcon={<ExpandMoreIcon />}>
            <Typography className={classes.heading}>Create Map from Template</Typography>
            <Typography className={classes.secondaryHeading}>General map creation</Typography>
          </ExpansionPanelSummary>
          <ExpansionPanelDetails>
            <Typography className={classes.content}>
              Allows for the creation of a map from a predefined template.
              <br />
              <br />
              Once a template is selected, the Map Layout Editor window
              appears with pre-defined nodes.
              <br />
              <br />
              The Simple Template consists of a root node (named ‘Start Node’)
              linked to a second node with a one-way, single arrow link icon.
            </Typography>
            <Button
              variant="outlined"
              color="primary"
              size="small"
              aria-label="Create"
              classes={{ root: classes.fab }}
              onClick={showModal}
              disabled={isDisabled}
            >
              Choose Template
              <ArrowForwardIcon
                fontSize="small"
                classes={{ root: classes.icon }}
              />
            </Button>
          </ExpansionPanelDetails>
        </ExpansionPanelMaterialUI>
      </ExpansionPanelWrapper>
    );
  }
}

export default withStyles(styles)(ExpansionPanel);
