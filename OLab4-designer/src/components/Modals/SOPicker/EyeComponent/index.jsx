// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import {
  List, ListItem, Popover, IconButton, CircularProgress, Typography,
} from '@material-ui/core';

import EyeIcon from '../../../../shared/assets/icons/eye.svg';

import ExpandableItem from '../../../../shared/components/ExpandableItem';

import * as scopedObjectsActions from '../../../../redux/scopedObjects/action';

import { POPOVER_ANCHOR, POPOVER_TRANSFORM, FILTER_VALUES } from './config';
import { splitAndCapitalize } from './utils';
import type { IEyeComponentProps, IEyeComponentState } from './types';

import styles from './styles';

class EyeComponent extends PureComponent<IEyeComponentProps, IEyeComponentState> {
  state: IEyeComponentState = {
    isShowTooltip: false,
    eyeIconRef: null,
  };

  // eslint-disable-next-line camelcase
  UNSAFE_componentWillReceiveProps(nextProps: IEyeComponentProps) {
    const { additionalInfo } = this.props;
    const isAdditionalInfoFetched = !additionalInfo && nextProps.additionalInfo;

    if (isAdditionalInfoFetched) {
      this.showTooltip();
    }
  }

  handleGetMoreInfo = (e: Event): void => {
    const {
      additionalInfo,
      scopedObjectId,
      scopedObjectType,
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED,
    } = this.props;

    if (!additionalInfo) {
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(scopedObjectId, scopedObjectType);
    } else {
      this.showTooltip();
    }

    this.setEyeIconRef(e.currentTarget.parentElement);
  }

  setEyeIconRef = (target: any): void => {
    this.setState({ eyeIconRef: target });
  }

  showTooltip = (): void => {
    this.setState({ isShowTooltip: true });
  }

  closeTooltip = (): void => {
    this.setState({
      isShowTooltip: false,
      eyeIconRef: null,
    });
  }

  render() {
    const { isShowTooltip, eyeIconRef } = this.state;
    const { isShowSpinner, additionalInfo, classes } = this.props;
    const isShowPopover = isShowTooltip && !isShowSpinner && Boolean(eyeIconRef);

    return (
      <div>
        {isShowSpinner ? (
          <CircularProgress
            size={16}
            classes={{ root: classes.circularProgress }}
          />
        ) : (
          <IconButton
            size="small"
            classes={{ root: classes.iconButton }}
            onClick={this.handleGetMoreInfo}
          >
            <EyeIcon />
          </IconButton>
        )}

        <Popover
          open={isShowPopover}
          anchorEl={eyeIconRef}
          onClose={this.closeTooltip}
          anchorOrigin={POPOVER_ANCHOR}
          transformOrigin={POPOVER_TRANSFORM}
          classes={{ paper: classes.popover }}
        >
          {Boolean(additionalInfo) && (
            <List className={classes.root}>
              {Object.keys(additionalInfo)
                .filter(key => !FILTER_VALUES.includes(additionalInfo[key]))
                .map((key, i, arr) => (
                  <ListItem
                    key={key}
                    divider={(i !== arr.length - 1)}
                  >
                    <Typography
                      variant="subtitle2"
                      className={classes.typography}
                    >
                      {`${splitAndCapitalize(key)}:`}
                    </Typography>
                    <ExpandableItem>{additionalInfo[key]}</ExpandableItem>
                  </ListItem>
                ))}
            </List>
          )}
        </Popover>
      </div>
    );
  }
}

const mapDispatchToProps = dispatch => ({
  ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: (scopedObjectId: number, scopedObjectType: string) => {
    dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(
      scopedObjectId,
      scopedObjectType,
    ));
  },
});

export default connect(
  null,
  mapDispatchToProps,
)(withStyles(styles)(EyeComponent));
