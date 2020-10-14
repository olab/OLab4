import React from 'react';
import styled, { css } from 'styled-components';
import { CardContent as CardContentMUI } from '@material-ui/core';

import { WHITE, ORANGE, DARK_GREY } from '../../../../../shared/colors';
import { HEADER_PADDING, HEADER_HEIGHT, BORDER_SIZE } from '../config';

export const CardContent = styled(({
  borderColor, isLocked, isSelected, ...rest
}) => <CardContentMUI {...rest} />)`
  overflow: hidden;
  position: relative;
  font-size: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 400px;
  min-width: 200px;
  min-height: 80px;
  letter-spacing: 0.06em;
  padding: 0 !important;
  border-top: none !important;
  border: ${BORDER_SIZE}px solid transparent;
  resize: ${({ isLocked }) => (isLocked ? 'none' : 'both')}

  ${({ isSelected }) => (isSelected && css`
    border-color: ${({ borderColor }) => borderColor || DARK_GREY};
    border-bottom-right-radius: 8px;
    border-bottom-left-radius: 8px;
  `)}

  & p, ul {
    margin: 0;
  }

  &::-webkit-scrollbar {
    width: 8px;
    height: 8px;
    cursor: pointer;
  }
  &::-webkit-scrollbar-thumb {
    border-radius: 8px;
    background-color: ${DARK_GREY};
    border: 1px solid ${WHITE};
    background-clip: padding-box;
  }
  &::-webkit-scrollbar-track {
    margin: 5px 10px 10px 5px;
  }
  &::-webkit-scrollbar-button {
    width: 0;
    height: 0;
    display: none;
  }
  &::-webkit-scrollbar-corner {
    background-color: transparent;
  }
  &::-webkit-resizer {
    display: none;
    -webkit-appearance: none;
  }
`;

const styles = {
  card: {
    display: 'inline-block',
    verticalAlign: 'top',
    flexDirection: 'column',
    position: 'relative',
    overflow: 'visible',
    borderRadius: 8,
    outline: 'none',
  },
  cardHeader: {
    paddingLeft: 10,
    backgroundColor: ORANGE,
    color: WHITE,
    fontSize: 18,
    lineHeight: 'normal',
    letterSpacing: '0.01em',
    padding: 0,
    height: HEADER_HEIGHT,
    borderRadius: '8px 8px 0 0',
    cursor: 'all-scroll',
    display: 'flex',
    alignItems: 'center',
  },
  cardHeaderCollapsed: {
    alignItems: 'end',
    paddingTop: HEADER_PADDING,
    paddingBottom: HEADER_PADDING,
    borderRadius: 8,
  },
  cardContentText: {
    padding: '5px 0 15px 10px',
    marginRight: 10,
  },
  cardHeaderRegular: {
    backgroundColor: DARK_GREY,
  },
  action: {
    marginTop: 0,
    marginRight: 0,
    alignSelf: 'unset',
  },
  title: {
    width: 80,
    '& p': {
      margin: 0,
    },
  },
  pos: {
    marginBottom: 12,
  },
  resizer: {
    position: 'absolute',
    pointerEvents: 'none',
    backgroundColor: 'transparent',
    borderRight: `2px solid ${WHITE}`,
    borderBottom: `2px solid ${WHITE}`,
    borderRadius: '0 0 8px 0',
    right: 0,
    bottom: 0,
    marginRight: 2,
    marginBottom: 2,
    width: 15,
    height: 15,
  },
  layout: {
    position: 'absolute',
    marginTop: 40,
    top: 0,
    right: 15,
    left: 0,
    bottom: 0,
  },
};

export default styles;
