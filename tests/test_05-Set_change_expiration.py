# Generated from Selenium IDE
# Test name: t05 - Set/change expiration
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from fn_switchuser import Test_fn_switchuser as Sub1

class Test_05_Set_change_expiration:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_05_Set_change_expiration(self):
    self.driver.get("http://127.0.0.1/")
    self.vars["username"] = "user1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionExternal").click()
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"firstname\"]").send_keys("REDCap")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"lastname\"]").send_keys("User2")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"email\"]").send_keys("user2@example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal button").click()
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowExpirationOptions").click()
    self.driver.execute_script("$('[data-projectname=\"User Management Wizard Test 1\"] [name=\"expiration\"]').val(new Date(Date.now()+864000000).toISOString().slice(0,10))")
    self.vars["expDate"] = self.driver.execute_script("var d=new Date($('[data-projectname=\"User Management Wizard Test 1\"] [name=\"expiration\"]').val()).toDateString();return d.slice(8,11) + d.slice(4,8) + d.slice(11,15)")
    self.driver.execute_script("//SETDESC:Enter date")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] [name=\"expiration\"]").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnSetExpiry").click()
    self.driver.execute_script("//SETDESC:Assert expiry date set")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1'][contains(.,'Access will expire on "+self.vars["expDate"]+".')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowExpirationOptions").click()
    self.vars["expDate"] = self.driver.execute_script("var d=new Date(Date.now()-86400000).toDateString();return d.slice(8,11) + d.slice(4,8) + d.slice(11,15)")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnRevokeNow").click()
    self.driver.execute_script("//SETDESC:Assert expiry set to date in past")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1'][contains(.,'Access expired on "+self.vars["expDate"]+".')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowExpirationOptions").click()
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnClearExp").click()
    self.driver.execute_script("//SETDESC:Assert expiry cleared")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1'][contains(.,'access to this project does not expire')]")) > 0
