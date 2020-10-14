import styled from 'styled-components';
import { GREY, DARK_GREY } from '../../../shared/colors';

export const ExpansionPanelWrapper = styled.div`
  margin-bottom: 1rem;
`;

const styles = theme => ({
  fab: {
    flexShrink: 0,
    alignSelf: 'flex-end',
    textDecoration: 'none',
  },
  icon: {
    marginLeft: '5px',
  },
  content: {
    marginRight: '2rem',
  },
  heading: {
    fontSize: theme.typography.pxToRem(15),
    fontWeight: 'bold',
    flexBasis: '33.33%',
    flexShrink: 0,
  },
  secondaryHeading: {
    fontSize: theme.typography.pxToRem(15),
    color: theme.palette.text.secondary,
  },
  list: {
    maxHeight: '40vh',
    overflowY: 'auto',
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
  listItem: {
    padding: '.15rem',
  },
  listButton: {
    width: '100%',
    textTransform: 'initial',
    textAlign: 'initial',
  },
});

export default styles;
