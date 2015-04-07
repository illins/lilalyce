<?php

namespace Wp {
  require_once("1k/base/view.php");
  require_once("1k/base/mail.php");
  require_once("wp/config.php");
  require_once("wapo/model.php");
  require_once("wp/form.php");

  require_once("user/api.php");

  class AdminTemplateView extends \Blink\TemplateView {

    public function require_login() {
      return true;
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/admin.twig");
    }

  }

  class PromotionCategoryListView extends \Blink\ListView {

    public function require_login() {
      return true;
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotioncategory_list.twig");
    }

    public function get_class() {
      $this->class = PromotionCategory::class_name();
      parent::get_class();
    }

    public function get_queryset() {
      $this->queryset = PromotionCategory::queryset()->order_by(array("name"));
    }

    public function get_context_data() {
      parent::get_context_data();
    }

  }

  class PromotionCategoryCreateView extends \Blink\CreateView {

    public function require_login() {
      return true;
    }

    public function get_class() {
      $this->class = PromotionCategory::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotioncategory_create.twig");
    }

    public function get_post_url() {
      $this->post_url = "/wapo/admin/promotioncategory/add/";
    }

    public function get_cancel_url() {
      $this->cancel_url = "/wapo/admin/promotioncategory/";
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/admin/promotioncategory/%s/", $this->object->id);
    }

  }

  class PromotionCategoryUpdateView extends \Blink\UpdateView {

    public function require_login() {
      return true;
    }

    public function get_class() {
      $this->class = PromotionCategory::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotioncategory_update.twig");
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/admin/promotioncategory/%s/update/", $this->object->id);
    }

    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/admin/promotioncategory/%s/", $this->object->id);
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/admin/promotioncategory/%s/", $this->object->id);
    }

  }

  class PromotionCategoryDetailView extends \Blink\DetailView {

    public function require_login() {
      return true;
    }

    public function get_class() {
      $this->class = PromotionCategory::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotioncategory_details.twig");
    }

    public function get_context_data() {
      parent::get_context_data();

      $this->context["promotioncategory_list"] = PromotionCategory::queryset()->order_by(array("name"))->all();
      $this->context["promotion_list"] = Promotion::queryset()->filter(array("promotioncategory" => $this->object->id))->fetch();
    }

  }

  class PromotionCreateView extends \Blink\CreateView {

    public function require_login() {
      return true;
    }
    
    public function get_class() {
      $this->class = Promotion::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotion_create.twig");
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/admin/promotioncategory/%s/promotion/add/", $this->request->param->param['promotioncategory_id']);
    }

    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/admin/promotioncategory/%s/", $this->request->param->param['promotioncategory_id']);
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/admin/promotioncategory/%s/promotion/%s/", $this->request->param->param['promotioncategory_id'], $this->object->id);
    }

  }
  
  /**
   * Activate or deactivate a promotion.
   */
  class PromotionStatusChangeRedirectView extends \Blink\RedirectView {
    public function get_redirect_url() {
      $promotion = Promotion::get_or_404(array("id"=>$this->request->param->param['pk']));
      
      if($promotion->active) {
        $promotion->active = 0;
        $promotion->save(sprintf("Promotion '%s' diactivated.", $promotion));
      } else {
        $promotion->active = 1;
        $promotion->save(sprintf("Promotion '%s' activated.", $promotion));
      }
      
      $this->redirect_url = sprintf("/wapo/admin/promotioncategory/%s/", $promotion->promotioncategory->id);
    }
  }

  class PromotionUpdateView extends \Blink\UpdateView {

    public function require_login() {
      return true;
    }

    public function get_class() {
      $this->class = Promotion::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotion_update.twig");
    }

    public function get_post_url() {
      $this->post_url = sprintf("/wapo/admin/promotioncategory/%s/promotion/%s/update/", $this->request->param->param['promotioncategory_id'], $this->object->id);
    }

    public function get_cancel_url() {
      $this->cancel_url = sprintf("/wapo/admin/promotioncategory/%s/promotion/%s/", $this->request->param->param['promotioncategory_id'], $this->object->id);
    }

    public function get_success_url() {
      $this->success_url = sprintf("/wapo/admin/promotioncategory/%s/promotion/%s/", $this->request->param->param['promotioncategory_id'], $this->object->id);
    }
    
    public function get_context_data() {
      parent::get_context_data();
    }

  }

  class PromotionDetailView extends \Blink\DetailView {

    public function require_login() {
      return true;
    }

    public function get_class() {
      $this->class = Promotion::class_name();
      parent::get_class();
    }

    protected function get_template() {
      $this->template_name = TemplateConfig::Template("admin/promotion_details.twig");
    }

  }

}
?>
