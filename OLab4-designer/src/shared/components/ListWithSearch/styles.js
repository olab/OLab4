import styled from 'styled-components';

import { GREY, DARK_GREY } from '../../colors';

export const SearchWrapper = styled.div`
  position: relative;
`;

export const ListItemContentWrapper = styled.div`
  width: 100%;

  &:hover {
    & button:last-of-type {
      opacity: 1;
    }
  }
`;

const styles = () => ({
  list: {
    marginBottom: 10,
    '&::-webkit-scrollbar': {
      width: 7,
      backgroundColor: GREY,
    },
    '&::-webkit-scrollbar-thumb': {
      backgroundColor: DARK_GREY,
      borderRadius: 4,
    },
    '&::-webkit-scrollbar-button': {
      width: 0,
      height: 0,
      display: 'none',
    },
    '&::-webkit-scrollbar-corner': {
      backgroundColor: 'transparent',
    },
  },
  listLimits: {
    maxHeight: '40vh',
    minHeight: '40vh',
    maxWidth: '40vw',
    minWidth: '40vw',
    overflowY: 'auto',
  },
  listEmpty: {
    marginTop: 10,
  },
  listItem: {
    padding: '.15rem',
    justifyContent: 'flex-end',
  },
  listButton: {
    position: 'relative',
    width: '100%',
    textTransform: 'initial',
    textAlign: 'initial',
  },
  searchField: {
    marginBottom: 5,
  },
  searchIcon: {
    position: 'absolute',
    right: 0,
    bottom: 7,
    padding: 3,
    fill: 'rgba(0, 0, 0, 0.42)',
    boxSizing: 'content-box',
  },
  deleteIcon: {
    position: 'absolute',
    opacity: 0,
    right: 10,
    top: '50%',
    padding: 5,
    transform: 'translate(0, -50%)',
    transition: 'opacity .25s ease',
  },
  secondaryText: {
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
  },
});

export default styles;
