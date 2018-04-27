/**
 * DefaultController.js
 * @author Scott Gibson
 */

const ControllerAbstract = use('EntradaJS/Controller/ControllerAbstract');

module.exports = class DefaultController extends ControllerAbstract
{
    indexAction() {
        return this.respond('Pages/Home.vue');
    }
};