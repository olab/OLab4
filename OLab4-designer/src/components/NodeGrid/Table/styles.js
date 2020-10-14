import styled from 'styled-components';
import SortIcon from '../../../shared/assets/icons/sort-arrows.svg';
import { MIDDLE_DARK_GREY, WHITE, DEEP_DARK_GREY } from '../../../shared/colors';

export const DefaultSortIcon = styled(SortIcon)`
  margin: 2px 5px;
`;

export const TableCellLabel = styled.div`
  display: flex;
  align-items: end;
  justify-content: center;

  ${({ isSortable }) => (isSortable && `
    cursor: pointer;
  `)}
`;

const styles = () => ({
  paper: {
    height: '84vh',
    overflow: 'auto',
  },
  tableRow: {
    '&:hover': {
      backgroundColor: `${MIDDLE_DARK_GREY}`,
    },
  },
  tableHeadCell: {
    borderBottomWidth: 0,
    paddingLeft: 0,
    paddingRight: '0!important',
    top: 0,
    left: 0,
    zIndex: 2,
    position: 'sticky',
    backgroundColor: `${WHITE}`,
    textAlign: 'center',
    verticalAlign: 'middle',
    fontSize: '1rem',
    border: `1px solid ${DEEP_DARK_GREY}`,
  },
  tableCell: {
    border: `1px solid ${DEEP_DARK_GREY}`,
    paddingTop: 15,
    paddingBottom: 15,
    paddingRight: 24,
    fontSize: '1rem',
    width: '15%',
    '&:first-of-type': {
      width: '5%',
    },
    '&:nth-of-type(3)': {
      width: '50%',
    },
    '&:nth-of-type(4)': {
      width: '10%',
    },
    '&:last-of-type': {
      width: '10%',
    },
  },
  horizontalDivider: {
    position: 'absolute',
    bottom: 0,
    zIndex: 5,
    width: '100%',
  },
});

export default styles;
