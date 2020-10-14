import { WHITE, BLUE } from '../../../../shared/colors';

const styles = () => ({
  tHead: {
    backgroundColor: `${WHITE}`,
    borderBottomColor: `${BLUE}`,
    borderBottomWidth: 5,
  },
  tHeadCell: {
    paddingBottom: 0,
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
  },
  tHeadCellSticky: {
    zIndex: 3,
  },
  tHeadCellLabel: {
    textAlign: 'right',
    marginBottom: 0,
    marginTop: 0,
  },
  tHeadCellLabelLeft: {
    textAlign: 'left',
    paddingRight: 0,
    paddingLeft: 22,
    marginBottom: 15.5,
    marginTop: 15.5,
  },
  icon: {
    height: 0,
    padding: 0,
    marginTop: 12,
    marginRight: 12,
    fontSize: '1.2rem',
  },
});

export default styles;
