ALTER TABLE  `wapo_profile` CHANGE  `location`  `fb_loc_id` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '103983392971091';
ALTER TABLE  `wapo_profile` ADD  `fb_loc_category` VARCHAR( 100 ) NOT NULL DEFAULT  'City',
ADD  `fb_loc_name` VARCHAR( 256 ) NOT NULL DEFAULT  'South Bend, Indiana';

create table wapo_distributor
(
id int not null auto_increment,
user_id int not null,
name varchar(100) not null,
street varchar(100) null,
city varchar(100) null,
state varchar(2) null,
zip int(5) null,
status boolean default 1,

constraint pk_wapo_distributor primary key (id),
constraint fk_wapo_distributor_user_id foreign key (user_id) references user_user (id),
unique(user_id)
)ENGINE=InnoDB;


create table wapo_profile
(
id int not null auto_increment,
distributor_id int not null,
name varchar(100) not null,
street varchar(100) null,
city varchar(100) null,
state varchar(2) null,
zip int(5) null,
status boolean default 1,

constraint pk_wapo_distributor primary key (id),
constraint fk_wapo_distributor_distributor_id foreign key (distributor_id) references wapo_distributor (id)
)ENGINE=InnoDB;

create table wapo_sociallinks
(
id int not null auto_increment,
profile_id int not null,
name varchar(100) not null,
link text not null,

constraint pk_wapo_sociallinks primary key (id),
constraint fk_wapo_sociallinks_profile_id foreign key (profile_id) references wapo_profile (id)
)ENGINE=InnoDB;


create table wapo_product
(
id int not null auto_increment,
profile_id int not null,
name varchar(100) not null,
description text,
url text not null,
photo_url text not null,

constraint pk_wapo_product primary key (id),
constraint fk_wapo_product foreign key (profile_id) references wapo_profile (id)
)ENGINE=InnoDB;

create table wapo_promotioncategory
(
id int not null auto_increment,
name varchar(100) not null,
description text null,

constraint pk_wapo_promotioncategory primary key (id),
unique(name)
)ENGINE=InnoDB;

create table wapo_promotion
(
id int not null auto_increment,
promotioncategory_id int not null,
name varchar(100) not null,
description text null,
cost float(7,2) not null default 0.0,
photo_url text null,
download_url text null,

constraint pk_wapo_promotion primary key (id),
constraint fk_wapo_promotion_promotioncategory_id foreign key (promotioncategory_id) references wapo_promotioncategory (id)
)ENGINE=InnoDB;

create table wapo_wapo
(
id int not null auto_increment,
profile_id int not null,
promotion_id int not null,
date_created datetime,
date_sent datetime,

constraint pk_wapo_wapo primary key (id),
constraint fk_wapo_wapo_profile_id foreign key (profile_id) references wapo_profile (id),
constraint fk_wapo_wapo_promotion_id foreign key (promotion_id) references wapo_promotion (id)
)ENGINE=InnoDB;

create table wapo_promotionrecipient
(
id int not null auto_increment,
wapo_id int not null,
email varchar(256) not null,
code varchar(50) not null,
confirm varchar(50) not null,
confirmed boolean not null default 0,
date_confirmed datetime not null,
downloaded boolean not null default 0,
date_downloaded datetime not null,

constraint pk_wapo_promotionrecipient primary key (id),
constraint fk_wapo_promotionrecipient_wapo_id foreign key (promotion_id) references wapo_wapo (id),
unique(code)
)ENGINE=InnoDB;

create table wapo_contact
(
id int not null auto_increment,
profile_id int not null,
name varchar(100) not null,
description text,

constraint pk_wapo_contact primary key (id),
constraint fk_wapo_contact_profile_id foreign key (profile_id) references wapo_profile (id)
);

create table wapo_contactitem
(
id int not null auto_increment,
contact_id int not null,
item varchar(256) not null,

constraint pk_wapo_contactitem primary key (id),
constraint fk_wapo_contactitem_contact_id foreign key (contact_id) references wapo_contact (id)
);















create table wapo_package
(
id int not null auto_increment,
package varchar(100) not null,
cost float(7,2) not null default 0.0,
description text,
active boolean default 1,

constraint pk_wapo_package primary key (id),
unique(package)
);

create table wapo_company
(
id int not null auto_increment,
package_id int not null,
company varchar(100) not null,
logo text,

constraint pk_wapo_company primary key (id),
constraint fk_wapo_company_package_id foreign key (package_id) references wapo_package (id)
);

alter table wapo_company add column package_id int not null default 1;
alter table wapo_company add constraint fk_wapo_company_package_id foreign key (package_id) references wapo_package(id);

create table wapo_member
(
id int not null auto_increment,
user_id int not null,
company_id int not null,
admin boolean default 0,

constraint pk_wapo_member primary key (id),
constraint fk_wapo_member_user_id foreign key (user_id) references user_user (id),
constraint fk_wapo_member_company_id foreign key (company_id) references wapo_company (id),
unique(user_id)
);

create table wapo_promotion
(
id int not null auto_increment,
promotion varchar(100) not null,
description text,

constraint pk_wapo_promotion primary key (id),
unique(promotion)
);

create table wapo_promotionproduct 
(
id int not null auto_increment,
promotion_id int not null,
name varchar(100) not null,
description text null,
price float(8,2) default 0.0,
photo_url text null default null,

constraint pk_wapo_promotionproduct primary key (id),
constraint fk_wapo_promotionproduct_promotion_id foreign key (promotion_id) references wapo_promotion (id)
) ENGINE=InnoDB;

create table wapo_delivery
(
id int not null auto_increment,
delivery varchar(100) not null,
description text,

constraint pk_wapo_promotion primary key (id),
unique(delivery)
);

create table wapo_status
(
id int not null auto_increment,
status varchar(100) not null,
description text,

constraint pk_wapo_status primary key (id),
unique(status)
);

create table wapo_promotionpackage
(
id int not null auto_increment,
company_id int not null,
promotion_id int not null,
package_id int not null,
status_id int not null,
title varchar(100) not null,
link text,
delivery_note text,
delivery_date datetime,

constraint pk_wapo_promotionpackage primary key (id),
constraint fk_wapo_promotionpackage_company_id foreign key (company_id) references wapo_company (id),
constraint fk_wapo_promotionpackage_promotion_id foreign key (promotion_id) references wapo_promotion (id),
constraint fk_wapo_promotionpackage_package_id foreign key (package_id) references wapo_package (id),
constraint fk_wapo_promotionpackage_status_id foreign key (status_id) references wapo_status (id)
);

alter table wapo_promotionpackage add column status_id int not null default 1;
alter table wapo_promotionpackage add constraint fk_wapo_promotionpackage_status_id foreign key (status_id) references wapo_status (id);
alter table wapo_promotionpackage add column title varchar(100) not null default '';

create table wapo_promotionpackagedelivery
(
id int not null auto_increment,
promotionpackage_id int not null,
delivery_id int not null,

constraint pk_wapo_promotionpackagedelivery primary key (id),
constraint fk_wapo_promotionpackagedelivery_promotionpackage_id foreign key (promotionpackage_id) references wapo_promotionpackage (id),
constraint fk_wapo_promotionpackagedelivery_delivery_id foreign key (delivery_id) references wapo_delivery (id)
);

$this->company = $models->IntegerField(array("null"=>FALSE));
      $this->package = $models->IntegerField(array("null"=>FALSE));
      $this->cost = $models->PositiveDecimalField(array("max_length"=>7,"decimal_places"=>2,"default"=>0.0));
      $this->purchased = $models->DateField(array("null"=>FALSE));
      $this->expires = $models->DateField(array("null"=>FALSE));
      $this->payment = $models->TextField();
      $this->auto_renew = $models->BooleanField(array("null"=>FALSE,"default"=>1));

create table wapo_companypackage
(
id int not null auto_increment,
company_id int not null,
package_id int not null,
cost float(7,2) default 0.0,
purchased date not null,
expires date not null,
payment text,
autoo_renew boolean not null default 1,

constraint pk_wapo_companypackage primary key (id),
constraint fk_wapo_companypackage_company_id foreign key (company_id) references wapo_company (id),
constraint fk_wapo_companypackage_package_id foreign key (package_id) references wapo_package (id)
)ENGINE=InnoDB;

create table wapo_companyproduct
(
id int not null auto_increment,
company_id int not null,
name varchar(100) not null,
description text,
url text not null,
photo_url text not null,

constraint pk_wapo_companyproduct primary key (id),
constraint fk_wapo_companyproduct foreign key (company_id) references wapo_company (id)
)ENGINE=InnoDB;

create table wapo_promotionpackagettoken
(
id int not null auto_increment,
promotionpackage_id int not null,
token varchar(200) not null,
confirm varchar(200) null default null,

constraint pk_wapo_promotionpackagettoken primary key (id),
constraint fk_wapo_promotionpackagettoken_promotionpackage_id foreign key (promotionpackage_id) references wapo_promotionpackage (id)
)ENGINE=InnoDB;



ALTER TABLE  `wapo_profile` ADD  `latitude` FLOAT( 10, 6 ) NOT NULL DEFAULT  '41.676355',
ADD  `longitude` FLOAT( 10, 6 ) NOT NULL DEFAULT  '-86.251990'