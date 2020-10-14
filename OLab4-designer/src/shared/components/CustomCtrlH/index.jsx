// @flow
import React, { Component } from 'react';
import { TextField } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import ToolbarItem from '../ToolbarItem';
import NextIcon from '../../assets/icons/next.svg';
import PreviousIcon from '../../assets/icons/previous.svg';
import ReplaceIcon from '../../assets/icons/replace.svg';
import ReplaceAllIcon from '../../assets/icons/replaceAll.svg';

import {
  getReplacedData, changeBackgroundInActiveElement, getHighlight, removeHighlight,
  getReplacedAllData, nextButton, prevButton, escapeSymbol,
} from './utils';

import { KEY_H, SEARCH_VALIDATE, FIELD_ERROR } from './config';

import type {
  Data as DataType,
  CustomCtrlHState as IState,
  CustomCtrlHProps as IProps,
} from './types';

import styles, { Container, Wrapper } from './styles';

class CustomCtrlH extends Component<IProps, IState> {
  constructor(props) {
    super(props);

    this.state = {
      data: props.data || [],
      search: '',
      replace: '',
      activeIndex: -1,
      isInputError: false,
      allMatches: [],
      highlightedItems: [],
    };
  }

  componentDidMount() {
    document.addEventListener('keydown', this.handleKeyPressed);
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleKeyPressed);
  }

  componentDidUpdate(prevProps: IProps) {
    const { search, highlightedItems: highlightedItemsState } = this.state;
    const { data, isShow } = this.props;
    const { data: prevData, isShow: isShowPrev } = prevProps;
    const highlightedItems = [...document.querySelectorAll('mark')];
    const isHighlightedItemsChange = highlightedItems.every(
      (item: HTMLElement): boolean => highlightedItemsState.includes(item),
    );

    if (!isHighlightedItemsChange) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ highlightedItems });
    }

    if (isShow !== isShowPrev) {
      const searchWord = isShow ? escapeSymbol(search) : '';

      this.highlight(searchWord);
    }

    if (prevData !== data) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        data,
        search: '',
        replace: '',
      });
    }
  }

  handleSearchPopupClose = (): void => {
    const { data } = this.state;
    const { onModalShow } = this.props;

    this.highlightRemoval(data);
    onModalShow(false);
  }

  handleKeyPressed = (e: KeyboardEvent): void => {
    const { onModalShow } = this.props;
    const isToggleCombination = e.keyCode === KEY_H && (e.ctrlKey || e.metaKey);

    if (isToggleCombination) {
      e.preventDefault();
      onModalShow();
    }
  }

  highlight = (search: string): void => {
    const { data } = this.state;
    const { fields } = this.props;
    const { resultData, allMatches } = getHighlight(fields, data, search);

    this.setState({
      allMatches,
      activeIndex: -1,
      data: resultData,
    });
  }

  highlightRemoval = (data: DataType): void => {
    const { fields, onStateChange } = this.props;
    const cleanData = removeHighlight(fields, data);

    onStateChange(cleanData);
    this.setState({
      search: '',
      replace: '',
      activeIndex: -1,
      data: cleanData,
    });
  }

  handleButtonClick = (isNextButton: boolean): Function => (): void => {
    const { activeIndex, highlightedItems } = this.state;
    const lastIndex = highlightedItems.length - 1;

    const { newActiveIndex, oldActiveIndex } = isNextButton
      ? nextButton(activeIndex, lastIndex)
      : prevButton(activeIndex, lastIndex);

    this.changeBackgroundColor(oldActiveIndex, newActiveIndex);
    this.setState({ activeIndex: newActiveIndex });
  }

  changeBackgroundColor = (activeIndex: number, newActiveIndex: number): void => {
    const { highlightedItems } = this.state;
    changeBackgroundInActiveElement(
      highlightedItems[newActiveIndex],
      highlightedItems[activeIndex],
    );
  }

  handleButtonReplace = (): void => {
    const {
      data, search, replace, activeIndex, allMatches,
    } = this.state;

    const foundElement = allMatches.find((item: Object<any>): void => {
      const isItemFound = Array(item.matchesInString).fill(0).map(
        (e: number, index: number): number => index + item.matchesAll - item.matchesInString,
      ).includes(activeIndex);

      return isItemFound;
    });

    if (foundElement) {
      const resultData = getReplacedData(data, foundElement, activeIndex, search, replace);
      this.highlightRemoval(resultData);
    }
  }

  handleButtonReplaceAll = (): void => {
    const { data, search, replace } = this.state;
    const { fields } = this.props;
    const replacedData = getReplacedAllData(data, fields, search, replace);

    this.highlightRemoval(replacedData);
  }

  handleSearchChange = (e: Event): void => {
    const { value } = e.target;
    const isValidValue = Boolean(value.match(SEARCH_VALIDATE));

    if (!isValidValue) {
      this.setState({ search: value, isInputError: false });
      this.highlight(escapeSymbol(value));

      return;
    }

    this.setState({ isInputError: true });
  }

  handleReplaceChange = (e: Event): void => {
    const { value } = e.target;
    this.setState({ replace: value });
  }

  render() {
    const {
      search, data, replace, isInputError,
    } = this.state;
    const { classes, children, isShow } = this.props;

    return (
      <>
        {isShow && (
          <Wrapper noValidate autoComplete="off">
            <Container>
              <TextField
                error={isInputError}
                helperText={isInputError && FIELD_ERROR}
                label="Search"
                value={search}
                className={classes.input}
                onChange={this.handleSearchChange}
                autoFocus
              />
              <ToolbarItem
                icon={PreviousIcon}
                label="Previous"
                classes={{ iconButton: classes.iconButton }}
                onClick={this.handleButtonClick(false)}
              />
              <ToolbarItem
                icon={NextIcon}
                label="Next"
                classes={{ iconButton: classes.iconButton }}
                onClick={this.handleButtonClick(true)}
              />
            </Container>
            <Container>
              <TextField
                label="Replace"
                value={replace}
                className={classes.input}
                onChange={this.handleReplaceChange}
              />
              <ToolbarItem
                icon={ReplaceIcon}
                label="Replace"
                classes={{ iconButton: classes.iconButton }}
                onClick={this.handleButtonReplace}
              />
              <ToolbarItem
                icon={ReplaceAllIcon}
                label="Replace All"
                classes={{ iconButton: classes.iconButton }}
                onClick={this.handleButtonReplaceAll}
              />
            </Container>
          </Wrapper>
        )}
        { children(data, this.highlightRemoval, this.handleSearchPopupClose) }
      </>
    );
  }
}

export default withStyles(styles)(CustomCtrlH);
