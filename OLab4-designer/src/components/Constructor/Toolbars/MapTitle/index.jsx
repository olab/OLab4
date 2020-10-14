// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import {
  FormControl, Input, FormHelperText, InputAdornment,
} from '@material-ui/core';

import PencilIcon from '../../../../shared/assets/icons/pencil.svg';

import * as mapDetailsActions from '../../../../redux/mapDetails/action';

import type { MapDetails } from '../../../../redux/mapDetails/types';
import type { IMapTitleProps, IMapTitleState } from './types';

import styles, { MapTitleWrapper } from './styles';

class MapTitle extends PureComponent<IMapTitleProps, IMapTitleState> {
  inputRef: HTMLInputElement | null;

  constructor(props: IMapTitleProps) {
    super(props);
    this.state = {
      title: props.title || '',
      isError: false,
      isFocused: false,
    };
  }

  // eslint-disable-next-line camelcase
  UNSAFE_componentWillReceiveProps(nextProps: IMapTitleProps) {
    this.setState({
      title: nextProps.title,
    });
  }

  handleChange = (e: Event): void => {
    const { value } = (e.target: window.HTMLInputElement);
    this.setState({
      title: value,
      isError: !value,
    });
  };

  handleGetRef = (instance): void => {
    const { title } = this.state;

    this.inputRef = instance;
    if (!title) {
      this.focusInput();
    }
  }

  handleFocus = (): void => {
    this.setState({ isFocused: true });
  }

  handleBlur = (): void => {
    const { title } = this.state;
    const { ACTION_UPDATE_MAP_DETAILS_REQUESTED } = this.props;

    if (!title) {
      this.setState({ isError: true });
      this.focusInput();

      return;
    }

    this.setState({
      isFocused: false,
      isError: false,
    });

    this.blurInput();

    ACTION_UPDATE_MAP_DETAILS_REQUESTED({ name: title });
  }

  handleSubmit = (e: Event): void => {
    if (e.preventDefault) {
      e.preventDefault();
    }

    this.blurInput();
  }

  focusInput = (): void => {
    if (this.inputRef) {
      this.inputRef.focus();
    }
  }

  blurInput = (): void => {
    if (this.inputRef) {
      this.inputRef.blur();
    }
  }

  render() {
    // eslint-disable-next-line no-unused-vars
    const { title, isFocused, isError } = this.state;
    const { classes } = this.props;

    return (
      <MapTitleWrapper onSubmit={this.handleSubmit}>
        <FormControl
          className={classes.formControl}
          error={isError}
        >
          <Input
            placeholder="Labyrinth name"
            classes={{
              root: classes.inputRoot,
              input: classes.input,
            }}
            title={title}
            value={title}
            onChange={this.handleChange}
            onFocus={this.handleFocus}
            inputRef={this.handleGetRef}
            onBlur={this.handleBlur}
            error={isError}
            autoComplete="off"
            disableUnderline={!isError}
            aria-describedby="component-error-text"
            startAdornment={(
              <InputAdornment className={classes.pencilIcon} position="start">
                <PencilIcon />
              </InputAdornment>
            )}
          />
          {isError && (
            <FormHelperText
              id="component-error-text"
              classes={{ root: classes.formHelperText }}
            >
              Please fill out name of the Map
            </FormHelperText>
          )}
        </FormControl>
      </MapTitleWrapper>
    );
  }
}

const mapStateToProps = ({ mapDetails }) => ({ title: mapDetails.name });

const mapDispatchToProps = dispatch => ({
  ACTION_UPDATE_MAP_DETAILS_REQUESTED: (mapDetails: MapDetails) => {
    dispatch(mapDetailsActions.ACTION_UPDATE_MAP_DETAILS_REQUESTED(mapDetails));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(MapTitle));
